<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Payment;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class PaymentsReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.payments-report';

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
        return __('messages.payments_report');
    }

    public function getTitle(): string
    {
        return __('messages.payments_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('payments')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        $payments = Payment::with(['sale', 'sale.client', 'sale.reseller'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $totalCollected = $payments->where('status', 'paid')->sum('amount');
        $totalPending   = $payments->where('status', 'pending_validation')->sum('amount');
        $cashTotal      = $payments->where('payment_method', 'cash')->where('status', 'paid')->sum('amount');
        $cardTotal      = $payments->where('payment_method', 'card')->where('status', 'paid')->sum('amount');
        $chequeTotal    = $payments->where('payment_method', 'cheque')->sum('amount');
        $transferTotal  = $payments->where('payment_method', 'bank_transfer')->where('status', 'paid')->sum('amount');

        $periodLabel = $this->periodLabel();

        return compact(
            'payments', 'totalCollected', 'totalPending',
            'cashTotal', 'cardTotal', 'chequeTotal', 'transferTotal', 'periodLabel'
        );
    }
}
