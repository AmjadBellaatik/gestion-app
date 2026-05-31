<?php

namespace App\Http\Controllers;

use App\Models\RepairTicket;

use Barryvdh\DomPDF\Facade\Pdf;

class RepairPrintController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REPAIR ORDER
    |--------------------------------------------------------------------------
    */

    public function printOrder(
        RepairTicket $repair
    ) {

        $pdf = Pdf::loadView(

            'pdf.repairs.order',

            [

                'repair' => $repair,

            ]
        );

        return $pdf->stream(

            'repair-order-' .

            $repair->ticket_number .

            '.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPAIR INVOICE
    |--------------------------------------------------------------------------
    */

    public function printInvoice(
        RepairTicket $repair
    ) {

        $pdf = Pdf::loadView(

            'pdf.repairs.invoice',

            [

                'repair' => $repair,

            ]
        );

        return $pdf->stream(

            'repair-invoice-' .

            $repair->ticket_number .

            '.pdf'
        );
    }
}