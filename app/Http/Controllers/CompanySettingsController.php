<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    use ResolvesCompany;

    public function edit() { return view('settings.company', ['company'=>$this->company()]); }

    public function update(Request $request)
    {
        $data=$request->validate(['name'=>'required|string|max:191','legal_name'=>'nullable|string|max:191','vat_number'=>'nullable|string|max:32','currency'=>'required|in:OMR','phone'=>'nullable|string|max:32','email'=>'nullable|email|max:191','address'=>'nullable|string|max:4000']);
        $this->company()->update($data);
        return back()->with('success','Business settings saved. These details will appear on new invoices and purchase records.');
    }
}
