<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Product;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class StockReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.stock-report';

    public function mount(): void
    {
        $this->initializePeriod();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('messages.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.stock_report');
    }

    public function getTitle(): string
    {
        return __('messages.stock_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('stock')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $products = Product::withSum(
            ['stockMovements as stock_in' => fn ($q) => $q->whereIn('type', ['entry', 'in', 'transfer', 'adjustment', 'return'])],
            'quantity'
        )
            ->withSum(
                ['stockMovements as stock_out' => fn ($q) => $q->whereIn('type', ['exit', 'out'])],
                'quantity'
            )
            ->get()
            ->map(function ($p) {
                $p->current_qty   = max(0, ($p->stock_in ?? 0) - ($p->stock_out ?? 0));
                $p->stock_value   = round($p->current_qty * (float) $p->purchase_price, 2);
                $p->is_low        = $p->stock_alert > 0 && $p->current_qty <= $p->stock_alert;
                return $p;
            })
            ->sortByDesc('stock_value');

        $movements = StockMovement::with(['product'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $totalProducts   = $products->count();
        $lowStockCount   = $products->where('is_low', true)->count();
        $totalValue      = $products->sum('stock_value');
        $movementsCount  = $movements->count();
        $entriesCount    = $movements->whereIn('type', ['entry', 'in'])->count();
        $exitsCount      = $movements->whereIn('type', ['exit', 'out'])->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'products', 'movements', 'totalProducts', 'lowStockCount',
            'totalValue', 'movementsCount', 'entriesCount', 'exitsCount', 'periodLabel'
        );
    }
}
