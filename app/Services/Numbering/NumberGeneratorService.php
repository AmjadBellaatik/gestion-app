<?php

namespace App\Services\Numbering;

use App\Models\CompanySetting;

class NumberGeneratorService
{
    public static function generate(
        string $type
    ): string {

        $companyId =
            session('company_id');

        $settings =

            CompanySetting::where(
                'company_id',
                $companyId
            )->first();

        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $count = rand(
            1,
            99999
        );

        $count = str_pad(
            $count,
            5,
            '0',
            STR_PAD_LEFT
        );

        $prefix = match ($type) {

            'invoice' =>

                $settings?->invoice_prefix
                ?? 'INV',

            'quotation' =>

                $settings?->quotation_prefix
                ?? 'DEV',

            'repair' =>

                $settings?->repair_prefix
                ?? 'REP',

            default => 'DOC',

        };

        return

            $prefix .

            '-' .

            $year .

            $month .

            '-' .

            $count;
    }
}