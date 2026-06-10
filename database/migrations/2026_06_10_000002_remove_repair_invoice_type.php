<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invoiceTypeId = DB::table('document_types')->where('code', 'INVOICE')->value('id');
        $repairTypeId  = DB::table('document_types')->where('code', 'REPAIR_INVOICE')->value('id');

        if (! $repairTypeId) {
            return; // Already absent — idempotent.
        }

        DB::transaction(function () use ($invoiceTypeId, $repairTypeId) {
            if ($invoiceTypeId) {
                DB::table('documents')
                    ->where('document_type_id', $repairTypeId)
                    ->update([
                        'document_type_id' => $invoiceTypeId,
                        'invoice_source'   => 'repair',
                    ]);
            }

            DB::table('document_sequences')
                ->where('document_type_id', $repairTypeId)
                ->delete();

            DB::table('document_templates')
                ->where('document_type_id', $repairTypeId)
                ->delete();

            DB::table('document_types')
                ->where('id', $repairTypeId)
                ->delete();
        });
    }

    public function down(): void
    {
        // Restoring a deleted document type with its data is not safe to automate.
    }
};
