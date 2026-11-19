<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\AiActivityLog;
use App\Models\AiAssistantSetting;
use App\Services\FinanceBuddyService;
use Illuminate\Http\Request;

class FinanceBuddyController extends Controller
{
    use ResolvesCompany;

    public function index(FinanceBuddyService $buddy)
    {
        $company = $this->company();
        $setting = AiAssistantSetting::firstOrCreate(['company_id' => $company->id]);

        return view('finance-buddy.index', ['company' => $company, 'setting' => $setting, 'context' => $buddy->context($company, auth()->user()), 'activities' => AiActivityLog::where('company_id', $company->id)->where('user_id', auth()->id())->latest()->take(8)->get()]);
    }

    public function ask(Request $request, FinanceBuddyService $buddy)
    {
        $data = $request->validate(['prompt' => 'required|string|max:2000']);
        $company = $this->company();
        $setting = AiAssistantSetting::firstOrCreate(['company_id' => $company->id]);
        $result = $buddy->respond($company, $request->user(), $data['prompt'], $setting);
        $result['prompt'] = $data['prompt'];
        AiActivityLog::create(['company_id' => $company->id, 'user_id' => $request->user()->id, 'provider' => $result['provider'], 'action' => 'assistant_request', 'prompt' => $data['prompt'], 'response' => $result['answer'], 'context_snapshot' => $result['context'], 'suggested_actions' => $result['actions'], 'status' => $result['status'], 'approval_required' => $result['status'] === 'needs_review']);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('finance-buddy.index')->with('buddy_response', $result);
    }

    public function settings()
    {
        $company = $this->company();

        return view('finance-buddy.settings', ['company' => $company, 'setting' => AiAssistantSetting::firstOrCreate(['company_id' => $company->id]), 'activities' => AiActivityLog::where('company_id', $company->id)->with('user')->latest()->take(25)->get()]);
    }

    public function updateSettings(Request $request)
    {
        $company = $this->company();
        $setting = AiAssistantSetting::firstOrCreate(['company_id' => $company->id]);
        $data = $request->validate(['enabled' => 'nullable|boolean', 'provider' => 'required|in:openai,anthropic', 'model' => 'nullable|string|max:100', 'api_key' => 'nullable|string|max:500', 'company_guidance' => 'nullable|string|max:4000', 'allow_draft_actions' => 'nullable|boolean', 'monthly_request_limit' => 'required|integer|min:10|max:100000']);
        $data['enabled'] = $request->boolean('enabled');
        $data['allow_draft_actions'] = $request->boolean('allow_draft_actions');
        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }
        $setting->update($data);

        return back()->with('success', 'Finance Buddy settings saved. API credentials are encrypted and never displayed after saving.');
    }
}
