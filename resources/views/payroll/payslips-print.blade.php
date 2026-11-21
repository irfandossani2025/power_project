<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>All payslips — {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</title>
    <style>
        @page { size: A4; margin: 13mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #18324b; margin: 0; }
        .toolbar { position: sticky; top: 0; padding: 14px 0; background: #fff; border-bottom: 1px solid #dce7ee; margin-bottom: 20px; }
        button { border: 0; border-radius: 7px; background: #129c82; color: #fff; padding: 10px 16px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .hint { color: #60798d; font-size: 13px; margin-left: 10px; }
        .payslip { min-height: 260mm; page-break-after: always; break-after: page; }
        .payslip:last-child { page-break-after: auto; break-after: auto; }
        .head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #1eaa91; padding-bottom: 16px; }
        h1 { margin: 0; font-size: 25px; } h2 { margin: 27px 0 8px; font-size: 17px; }
        table { width: 100%; border-collapse: collapse; } td { padding: 8px 9px; border-bottom: 1px solid #dde7ee; }
        .amount { text-align: right; font-weight: 600; }.total td { font-size: 17px; font-weight: bold; border-top: 2px solid #18324b; }.muted { color: #60798d; font-size: 13px; }
        @media print { .toolbar { display: none; } .payslip { min-height: 0; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Print / Save all payslips as PDF</button><span class="hint">Select “Save as PDF” in the print window to create one file for all employees.</span></div>
    @foreach($payroll->entries as $entry)
        <article class="payslip">
            <div class="head"><div><h1>{{ $company->name }}</h1><div class="muted">Salary payslip · {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</div></div><div><b>{{ $entry->employee_name }}</b><br><span class="muted">{{ $entry->employee_number }}</span></div></div>
            <h2>Earnings</h2><table><tr><td>Basic salary</td><td class="amount">OMR {{ number_format($entry->basic_salary, 3) }}</td></tr><tr><td>Housing allowance</td><td class="amount">OMR {{ number_format($entry->housing_allowance, 3) }}</td></tr><tr><td>Transport allowance</td><td class="amount">OMR {{ number_format($entry->transport_allowance, 3) }}</td></tr><tr><td>Other allowance</td><td class="amount">OMR {{ number_format($entry->other_allowance, 3) }}</td></tr><tr><td>Overtime / bonus</td><td class="amount">OMR {{ number_format($entry->overtime_amount + $entry->bonus_amount, 3) }}</td></tr><tr class="total"><td>Gross pay</td><td class="amount">OMR {{ number_format($entry->gross_pay, 3) }}</td></tr></table>
            <h2>Deductions</h2><table><tr><td>Employee statutory contribution</td><td class="amount">OMR {{ number_format($entry->employee_contribution, 3) }}</td></tr><tr><td>Unpaid leave, advance and other deductions</td><td class="amount">OMR {{ number_format($entry->unpaid_leave_deduction + $entry->advance_deduction + $entry->other_deduction, 3) }}</td></tr><tr class="total"><td>Net salary payable</td><td class="amount">OMR {{ number_format($entry->net_pay, 3) }}</td></tr></table>
            <p class="muted">Prepared in PowerBooks. Payment remains subject to company approval and Bank Muscat processing. {{ $entry->remarks }}</p>
        </article>
    @endforeach
</body>
</html>
