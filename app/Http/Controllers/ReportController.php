<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Company;

use App\Services\Reports\ReportService;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Restrict company list to companies the authenticated user belongs to.
        $userCompanyIds = $user->companies()->pluck('companies.id');

        // Validate that company_id filter, if supplied, is one the user is authorised for.
        $requestedCompanyId = $request->company_id;
        if ($requestedCompanyId && ! $userCompanyIds->contains((int) $requestedCompanyId)) {
            $requestedCompanyId = null;
        }

        $filters = [
            'date_from'  => $request->date_from,
            'date_to'    => $request->date_to,
            'company_id' => $requestedCompanyId,
            'language'   => $request->language,
            'user_id'    => $request->user_id,
        ];

        return view(
            'reports.index',
            [
                'filters'        => $filters,
                'companies'      => Company::whereIn('id', $userCompanyIds)->get(),
                'sales'          => ReportService::sales($filters),
                'repairs'        => ReportService::repairs($filters),
                'stock'          => ReportService::stock($filters),
                'credits'        => ReportService::resellerCredits(),
                'reimbursements' => ReportService::reimbursements(),
                'profits'        => ReportService::profits($filters),
            ]
        );
    }
}