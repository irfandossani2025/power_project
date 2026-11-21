<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use ResolvesCompany;

    public function index()
    {
        $company = $this->company();
        $runs = PayrollRun::where('company_id', $company->id)->latest('period_year')->latest('period_month')->get();

        return view('payroll.index', compact('company', 'runs'));
    }

    public function settings()
    {
        $company = $this->company();
        $setting = PayrollSetting::firstOrCreate(['company_id' => $company->id]);

        return view('payroll.settings', compact('company', 'setting'));
    }

    public function updateSettings(Request $request)
    {
        $setting = PayrollSetting::firstOrCreate(['company_id' => $this->company()->id]);
        $setting->update($request->validate(['payroll_day' => 'required|integer|between:1,31', 'omani_employee_rate' => 'required|numeric|between:0,100', 'omani_employer_rate' => 'required|numeric|between:0,100', 'non_omani_provision_rate' => 'required|numeric|between:0,100', 'overtime_day_multiplier' => 'required|numeric|min:1', 'overtime_night_multiplier' => 'required|numeric|min:1', 'bank_muscat_debit_account' => 'nullable|string|max:64', 'bank_muscat_customer_number' => 'nullable|string|max:32', 'bank_profile_confirmed' => 'nullable|boolean']) + ['bank_profile_confirmed' => $request->boolean('bank_profile_confirmed')]);

        return back()->with('success', 'Payroll policy settings saved. Confirm statutory rates with your accountant before approving payroll.');
    }

    public function compensation(Employee $employee)
    {
        $this->ensure($employee);
        $compensation = EmployeeCompensation::firstOrCreate(['employee_id' => $employee->id]);

        return view('payroll.compensation', compact('employee', 'compensation'));
    }

    public function updateCompensation(Request $request, Employee $employee)
    {
        $this->ensure($employee);
        $compensation = EmployeeCompensation::firstOrCreate(['employee_id' => $employee->id]);
        $data = $request->validate(['basic_salary' => 'required|numeric|min:0', 'housing_allowance' => 'nullable|numeric|min:0', 'transport_allowance' => 'nullable|numeric|min:0', 'other_allowance' => 'nullable|numeric|min:0', 'bank_name' => 'nullable|string|max:120', 'bank_account_number' => 'nullable|string|max:80', 'account_holder_name' => 'nullable|string|max:255']);
        foreach (['housing_allowance', 'transport_allowance', 'other_allowance'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }
        $compensation->update($data);

        return redirect()->route('payroll.index')->with('success', 'Employee salary profile saved.');
    }

    public function create()
    {
        return view('payroll.create', ['company' => $this->company()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['period_year' => 'required|integer|between:2020,2100', 'period_month' => 'required|integer|between:1,12']);
        $company = $this->company();
        $run = PayrollRun::create($data + ['company_id' => $company->id, 'prepared_by' => auth()->id()]);
        $setting = PayrollSetting::firstOrCreate(['company_id' => $company->id]);
        Employee::where('company_id', $company->id)->where('is_active', true)->with('compensation')->get()->each(function ($employee) use ($run, $setting) {
            $c = $employee->compensation;
            if (! $c) {
                return;
            } $basic = (float) $c->basic_salary;
            $allowances = (float) $c->housing_allowance + (float) $c->transport_allowance + (float) $c->other_allowance;
            $employeeContribution = $employee->is_omani ? round($basic * (float) $setting->omani_employee_rate / 100, 3) : 0;
            $employerContribution = $employee->is_omani ? round($basic * (float) $setting->omani_employer_rate / 100, 3) : 0;
            $provision = ! $employee->is_omani ? round($basic * (float) $setting->non_omani_provision_rate / 100, 3) : 0;
            PayrollEntry::create(['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'employee_number' => $employee->employee_number, 'employee_name' => $employee->full_name, 'is_omani' => $employee->is_omani, 'basic_salary' => $basic, 'housing_allowance' => $c->housing_allowance, 'transport_allowance' => $c->transport_allowance, 'other_allowance' => $c->other_allowance, 'employee_contribution' => $employeeContribution, 'employer_contribution' => $employerContribution, 'non_omani_provision' => $provision, 'gross_pay' => $basic + $allowances, 'net_pay' => $basic + $allowances - $employeeContribution]);
        });
        $this->totals($run);

        return redirect()->route('payroll.show', $run)->with('success', 'Draft payroll created. Review every entry before approval.');
    }

    public function show(PayrollRun $payroll)
    {
        $this->ensureRun($payroll);
        $payroll->load('entries');

        return view('payroll.show', ['company' => $this->company(), 'payroll' => $payroll]);
    }

    public function updateEntry(Request $request, PayrollRun $payroll, PayrollEntry $entry)
    {
        $this->ensureRun($payroll);
        abort_unless($payroll->status === 'draft' && $entry->payroll_run_id === $payroll->id, 422);
        $data = $request->validate(['overtime_amount' => 'nullable|numeric|min:0', 'bonus_amount' => 'nullable|numeric|min:0', 'unpaid_leave_deduction' => 'nullable|numeric|min:0', 'advance_deduction' => 'nullable|numeric|min:0', 'other_deduction' => 'nullable|numeric|min:0', 'remarks' => 'nullable|string|max:1000']);
        foreach (['overtime_amount', 'bonus_amount', 'unpaid_leave_deduction', 'advance_deduction', 'other_deduction'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }
        $entry->update($data);
        $gross = (float) $entry->basic_salary + (float) $entry->housing_allowance + (float) $entry->transport_allowance + (float) $entry->other_allowance + (float) $entry->overtime_amount + (float) $entry->bonus_amount;
        $entry->update(['gross_pay' => $gross, 'net_pay' => $gross - (float) $entry->employee_contribution - (float) $entry->unpaid_leave_deduction - (float) $entry->advance_deduction - (float) $entry->other_deduction]);
        $this->totals($payroll);

        return back()->with('success', 'Payroll entry recalculated.');
    }

    public function approve(PayrollRun $payroll)
    {
        $this->ensureRun($payroll);
        abort_unless($payroll->status === 'draft', 422);
        $payroll->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Payroll approved and locked. Send the reviewed payment file through Bank Muscat separately.');
    }

    public function payslip(PayrollRun $payroll, PayrollEntry $entry)
    {
        $this->ensureRun($payroll);
        abort_unless($entry->payroll_run_id === $payroll->id, 404);

        return view('payroll.payslip', ['company' => $this->company(), 'payroll' => $payroll, 'entry' => $entry]);
    }

    public function export(PayrollRun $payroll)
    {
        $this->ensureRun($payroll);
        abort_unless($payroll->status === 'approved', 422);
        $setting = PayrollSetting::firstOrCreate(['company_id' => $payroll->company_id]);
        abort_unless($setting->bank_profile_confirmed, 422, 'Confirm the Bank Muscat profile after checking your corporate portal before exporting.');
        $rows = $payroll->entries()->with('employee.compensation')->get();

        return response()->streamDownload(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Employee Number', 'Employee Name', 'Bank', 'Account Number', 'Net Salary OMR']);
            foreach ($rows as $entry) {
                fputcsv($f, [$entry->employee_number, $entry->employee_name, optional($entry->employee->compensation)->bank_name, optional($entry->employee->compensation)->bank_account_number, number_format($entry->net_pay, 3, '.', '')]);
            } fclose($f);
        }, 'bank-muscat-salary-review.csv', ['Content-Type' => 'text/csv']);
    }

    private function totals(PayrollRun $run): void
    {
        $run->update(['gross_total' => $run->entries()->sum('gross_pay'), 'net_total' => $run->entries()->sum('net_pay'), 'employer_cost_total' => $run->entries()->sum('employer_contribution') + $run->entries()->sum('non_omani_provision')]);
    }

    private function ensure(Employee $e): void
    {
        abort_unless($e->company_id === $this->company()->id, 404);
    }

    private function ensureRun(PayrollRun $p): void
    {
        abort_unless($p->company_id === $this->company()->id, 404);
    }
}
