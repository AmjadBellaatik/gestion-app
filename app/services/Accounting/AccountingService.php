<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

use Illuminate\Support\Facades\DB;

class AccountingService
{
    public static function createEntry(
        array $data
    ): JournalEntry {

        return DB::transaction(

            function () use ($data) {

                $entry = JournalEntry::create([

                    'company_id' =>

                        $data['company_id'],

                    'date' =>

                        $data['date']
                        ?? now(),

                    'reference' =>

                        $data['reference'],

                    'description' =>

                        $data['description']
                        ?? null,

                ]);

                foreach (

                    $data['lines']

                    as $line

                ) {

                    JournalEntryLine::create([

                        'journal_entry_id' =>

                            $entry->id,

                        'account_code' =>

                            $line['account_code'],

                        'debit' =>

                            $line['debit']
                            ?? 0,

                        'credit' =>

                            $line['credit']
                            ?? 0,

                    ]);
                }

                return $entry;
            }
        );
    }
}