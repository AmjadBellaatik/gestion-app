<?php

namespace App\Http\Controllers;

use App\Models\Company;

use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function switch(
        Request $request
    )
    {
        $validated = $request->validate([

            'company_id' => [

                'required',
                'exists:companies,id',

            ],

        ]);

        $company = auth()
            ->user()
            ->companies()
            ->where(
                'companies.id',
                $validated['company_id']
            )
            ->first();

        abort_unless(
            $company,
            403
        );

        abort_if(
            strcasecmp(
                trim($company->name),
                'Default Company'
            ) === 0,
            403
        );

        session()->put(

            'company_id',

            $company->id

        );

        return back();
    }
}
