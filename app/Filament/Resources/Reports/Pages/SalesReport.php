<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Models\Sale;

use BackedEnum;
use UnitEnum;

use Filament\Pages\Page;

class SalesReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected string $view =
        'filament.pages.sales-report';

    public $company_id = null;

    public $user_id = null;

    public $language = null;

    public $from = null;

    public $until = null;

    public $sales = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('messages.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.sales_report');
    }

    public function mount(): void
    {
        $this->loadSales();
    }

    public function updated(): void
    {
        $this->loadSales();
    }

    public function loadSales(): void
    {
        $query = Sale::query();

        if ($this->company_id) {

            $query->where(
                'company_id',
                $this->company_id
            );
        }

        if ($this->user_id) {

            $query->where(
                'user_id',
                $this->user_id
            );
        }

        if ($this->language) {

            $query->where(
                'language',
                $this->language
            );
        }

        if ($this->from) {

            $query->whereDate(
                'created_at',
                '>=',
                $this->from
            );
        }

        if ($this->until) {

            $query->whereDate(
                'created_at',
                '<=',
                $this->until
            );
        }

        $this->sales = $query

            ->latest()

            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
