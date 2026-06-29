<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\GeneratedPdf;
use App\Models\Scopes\CompanyScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Remove generated document PDFs on disk that are no longer referenced by any
 * document (pdf_path) or generated_pdfs record — orphans left behind by older
 * regenerations/deletions before the storePdf() clean-up was in place.
 *
 *   php artisan documents:prune-orphan-pdfs           (dry run)
 *   php artisan documents:prune-orphan-pdfs --delete  (actually delete)
 */
class PruneOrphanPdfs extends Command
{
    protected $signature = 'documents:prune-orphan-pdfs {--delete : Delete the orphan files (otherwise just report)}';

    protected $description = 'Delete orphaned generated document PDF files that are no longer referenced.';

    public function handle(): int
    {
        $disk = Storage::disk('public');

        $referenced = Document::withoutGlobalScope(CompanyScope::class)
            ->whereNotNull('pdf_path')->pluck('pdf_path')
            ->merge(GeneratedPdf::withoutGlobalScopes()->whereNotNull('path')->pluck('path'))
            ->filter()->unique()->values()->all();
        $referenced = array_flip($referenced);

        $all = collect($disk->allFiles('documents'))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.pdf'));

        $orphans = $all->reject(fn ($f) => isset($referenced[$f]))->values();

        $this->info("PDF files: {$all->count()} | referenced: " . count($referenced) . " | orphans: {$orphans->count()}");

        if ($orphans->isEmpty()) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        if (! $this->option('delete')) {
            $this->warn('Dry run — re-run with --delete to remove the ' . $orphans->count() . ' orphan file(s).');
            return self::SUCCESS;
        }

        $bytes = 0;
        foreach ($orphans as $f) {
            $bytes += (int) $disk->size($f);
            $disk->delete($f);
        }
        $this->info('Deleted ' . $orphans->count() . ' orphan PDF(s), freed ' . round($bytes / 1048576, 1) . ' MB.');

        return self::SUCCESS;
    }
}
