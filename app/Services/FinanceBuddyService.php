<?php

namespace App\Services;

use App\Models\AiAssistantSetting;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseBill;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class FinanceBuddyService
{
    public function context(Company $company, User $user): array
    {
        $canSeeFinance = in_array($user->role, ['admin', 'accountant']);
        $invoices = Invoice::where('company_id', $company->id)->whereNotIn('status', ['draft', 'void']);
        $products = Product::where('company_id', $company->id)->where('type', 'product')->where('is_active', true);
        $lowStock = (clone $products)->where('reorder_level', '>', 0)->withSum('stockMovements as stock_on_hand', 'quantity')->get()
            ->filter(fn ($product) => (float) ($product->stock_on_hand ?? 0) <= (float) $product->reorder_level);

        return array_filter([
            'currency' => $company->currency ?: 'OMR',
            'role' => $user->role,
            'active_customers' => $company->customers()->where('is_active', true)->count(),
            'active_items' => $products->count(),
            'low_stock_items' => $lowStock->count(),
            'active_projects' => Project::where('company_id', $company->id)->where('status', 'active')->count(),
            'open_quotations' => Quotation::where('company_id', $company->id)->whereIn('status', ['draft', 'sent'])->count(),
            'receivables_omr' => $canSeeFinance ? round((float) (clone $invoices)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'), 3) : null,
            'overdue_invoices' => $canSeeFinance ? (clone $invoices)->where(function ($query) {
                $query->where('status', 'overdue')->orWhere(function ($q) {
                    $q->whereNotIn('status', ['paid'])->whereDate('due_date', '<', today());
                });
            })->count() : null,
            'month_sales_omr' => $canSeeFinance ? round((float) (clone $invoices)->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'), 3) : null,
            'month_expenses_omr' => $canSeeFinance ? round((float) Expense::where('company_id', $company->id)->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'), 3) : null,
            'open_payables_omr' => $canSeeFinance ? round((float) PurchaseBill::where('company_id', $company->id)->whereIn('status', ['received', 'partially_paid'])->sum('balance_due'), 3) : null,
        ], fn ($value) => $value !== null);
    }

    public function respond(Company $company, User $user, string $prompt, ?AiAssistantSetting $setting): array
    {
        $context = $this->context($company, $user);
        $local = $this->localResponse($prompt, $context, $user);

        if (! $setting?->enabled || blank($setting->api_key)) {
            return $local + ['provider' => 'guided', 'context' => $context];
        }

        try {
            $reply = $setting->provider === 'anthropic'
                ? $this->askAnthropic($setting, $prompt, $context, $user)
                : $this->askOpenAi($setting, $prompt, $context, $user);

            return ['answer' => $reply, 'actions' => $local['actions'], 'status' => $local['status'], 'provider' => $setting->provider, 'context' => $context];
        } catch (\Throwable $exception) {
            return $local + ['provider' => 'guided', 'context' => $context, 'notice' => 'The external AI provider could not be reached, so Finance Buddy used its secure in-app guidance instead.'];
        }
    }

    private function localResponse(string $prompt, array $context, User $user): array
    {
        $question = mb_strtolower($prompt);
        $currency = $context['currency'] ?? 'OMR';
        $actions = [];
        $status = 'completed';

        if (str_contains($question, 'overdue') || str_contains($question, 'receivable') || str_contains($question, 'invoice')) {
            $answer = isset($context['receivables_omr'])
                ? "There are {$context['overdue_invoices']} overdue invoice(s) and open receivables of {$currency} ".number_format((float) $context['receivables_omr'], 3).'. I have not changed any records.'
                : 'Your role does not have permission to view receivables or overdue invoice amounts. Please ask an administrator or accountant to review this.';
            $actions[] = ['label' => 'Open invoices', 'url' => route('invoices.index'), 'type' => 'review'];
        } elseif (str_contains($question, 'stock') || str_contains($question, 'inventory') || str_contains($question, 'reorder')) {
            $answer = "There are {$context['low_stock_items']} item(s) at or below their reorder level. You can review stock movements and create a purchase draft; no stock has been adjusted.";
            $actions[] = ['label' => 'Review inventory', 'url' => route('inventory.index'), 'type' => 'review'];
            $actions[] = ['label' => 'Create purchase draft', 'url' => route('purchases.create'), 'type' => 'draft'];
        } elseif (str_contains($question, 'quotation') || str_contains($question, 'quote')) {
            $answer = 'I can prepare a quotation safely as a draft. Select the customer, items, prices, VAT treatment and terms on the next screen, then review it before saving or sending.';
            $actions[] = ['label' => 'Create quotation draft', 'url' => route('quotations.create'), 'type' => 'draft'];
            $status = 'needs_review';
        } elseif (str_contains($question, 'expense') || str_contains($question, 'cost')) {
            $amount = $context['month_expenses_omr'] ?? null;
            $answer = $amount !== null ? "Expenses recorded this month total {$currency} ".number_format((float) $amount, 3).'. I can help classify a new expense, but it will remain a draft until an authorized user reviews it.' : 'I can help prepare an expense draft, but your role cannot view accounting totals.';
            $actions[] = ['label' => 'Create expense draft', 'url' => route('expenses.create'), 'type' => 'draft'];
            $status = 'needs_review';
        } elseif (str_contains($question, 'project') || str_contains($question, 'margin') || str_contains($question, 'profit')) {
            $answer = "There are {$context['active_projects']} active project(s). Open a project to review its budget, sales, costs, billed value and estimated margin. Financial changes always require review.";
            $actions[] = ['label' => 'Open projects', 'url' => route('projects.index'), 'type' => 'review'];
        } else {
            $answer = "I can help with quotations, invoices, expenses, purchases, stock, projects and reports. Right now your workspace has {$context['active_customers']} active customer(s), {$context['active_items']} active item(s), {$context['open_quotations']} open quotation(s), and {$context['low_stock_items']} low-stock item(s). Tell me what you would like to review or prepare.";
            $actions[] = ['label' => 'View reports', 'url' => route('reports.index'), 'type' => 'review'];
        }

        return compact('answer', 'actions', 'status');
    }

    private function systemPrompt(User $user, array $context, ?string $guidance): string
    {
        return 'You are Finance Buddy inside a private Oman accounting and inventory application. You provide concise, practical accounting-operation guidance. Never claim to be a licensed Chartered Accountant, never invent data, and never state that a financial record was changed. All write actions require an authorized user review. Respect the user role: '.$user->role.'. Only use this aggregated workspace context: '.json_encode($context).'. Company guidance: '.($guidance ?: 'None configured.');
    }

    private function askOpenAi(AiAssistantSetting $setting, string $prompt, array $context, User $user): string
    {
        $response = Http::timeout(20)->withToken($setting->api_key)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $setting->model ?: 'gpt-4.1-mini',
            'messages' => [['role' => 'system', 'content' => $this->systemPrompt($user, $context, $setting->company_guidance)], ['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
        ])->throw()->json();

        return data_get($response, 'choices.0.message.content') ?: 'I could not prepare a response from the configured provider.';
    }

    private function askAnthropic(AiAssistantSetting $setting, string $prompt, array $context, User $user): string
    {
        $response = Http::timeout(20)->withHeaders(['x-api-key' => $setting->api_key, 'anthropic-version' => '2023-06-01'])->post('https://api.anthropic.com/v1/messages', [
            'model' => $setting->model ?: 'claude-3-5-haiku-latest',
            'max_tokens' => 700,
            'system' => $this->systemPrompt($user, $context, $setting->company_guidance),
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ])->throw()->json();

        return data_get($response, 'content.0.text') ?: 'I could not prepare a response from the configured provider.';
    }
}
