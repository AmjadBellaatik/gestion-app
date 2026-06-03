<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Traits\HasReportPeriod;
use App\Models\Client;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ClientsReport extends Page
{
    use HasReportPeriod;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.clients-report';

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
        return __('messages.clients_report');
    }

    public function getTitle(): string
    {
        return __('messages.clients_report');
    }

    protected function getHeaderActions(): array
    {
        return [$this->pdfExportAction('clients')];
    }

    public function getViewData(): array
    {
        [$from, $to] = $this->resolveRange();

        // All clients (snapshot), new ones in range
        $clients = Client::withCount('sales')
            ->withSum('sales', 'total')
            ->withSum('sales', 'paid_amount')
            ->orderBy('created_at', 'desc')
            ->get();

        $newInPeriod    = $clients->whereBetween('created_at', [$from, $to])->count();
        $totalClients   = $clients->count();
        $activeClients  = $clients->where('is_active', true)->count();
        $blockedClients = $clients->where('is_blocked', true)->count();
        $persons        = $clients->where('client_type', 'person')->count();
        $companies      = $clients->where('client_type', 'company')->count();
        $administrations = $clients->where('client_type', 'administration')->count();

        $periodLabel = $this->periodLabel();

        return compact(
            'clients', 'totalClients', 'activeClients', 'blockedClients',
            'persons', 'companies', 'administrations', 'newInPeriod', 'periodLabel'
        );
    }
}
