<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;

trait ResolvesCompany
{
    protected function company(): Company
    {
        return Company::firstOrCreate(
            ['name' => 'My Business'],
            ['legal_name' => 'My Business', 'currency' => 'OMR']
        );
    }
}
