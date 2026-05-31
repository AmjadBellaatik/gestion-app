<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Company;

use App\Services\Reports\ReportService;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = [

            'date_from' =>
                $request->date_from,

            'date_to' =>
                $request->date_to,

            'company_id' =>
                $request->company_id,

            'language' =>
                $request->language,

            'user_id' =>
                $request->user_id,

        ];

        return view(

            'reports.index',

            [

                'filters' => $filters,

                'companies' =>
                    Company::all(),

                'sales' =>
                    ReportService::sales(
                        $filters
                    ),

                'repairs' =>
                    ReportService::repairs(
                        $filters
                    ),

                'stock' =>
                    ReportService::stock(
                        $filters
                    ),

                'credits' =>
                    ReportService::resellerCredits(),

                'reimbursements' =>
                    ReportService::reimbursements(),

                'profits' =>
                    ReportService::profits(
                        $filters
                    ),

            ]

        );
    }
}