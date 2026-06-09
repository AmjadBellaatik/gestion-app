<?php

namespace App\Filament\Widgets;

use App\Models\RepairTicket;
use App\Models\Technician;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TechnicianProductivityWidget
    extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /*
        |--------------------------------------------------------------------------
        | COMPLETED REPAIRS
        |--------------------------------------------------------------------------
        */

        $completedRepairs =
            RepairTicket::where(
                'status',
                'completed'
            )->count();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE TICKETS
        |--------------------------------------------------------------------------
        */

        $activeTickets =
            RepairTicket::whereNotIn(

                'status',

                [

                    'completed',
                    'delivered',
                    'closed',
                    'cancelled',

                ]
            )->count();

        /*
        |--------------------------------------------------------------------------
        | AVERAGE REPAIR TIME
        |--------------------------------------------------------------------------
        */

        $averageRepairTime =
            RepairTicket::whereNotNull(
                'repair_started_at'
            )

            ->whereNotNull(
                'completed_at'
            )

            ->get()

            ->avg(function ($repair) {

                return $repair
                    ->repair_started_at
                    ->diffInHours(

                        $repair->completed_at
                    );
            });

        /*
        |--------------------------------------------------------------------------
        | GENERATED REVENUE
        |--------------------------------------------------------------------------
        */

        $generatedRevenue =
            Technician::sum(
                'generated_revenue'
            );

        return [

            /*
            |--------------------------------------------------------------------------
            | COMPLETED REPAIRS
            |--------------------------------------------------------------------------
            */

            Stat::make(

                __('messages.completed_repairs'),

                $completedRepairs
            )

                ->description(
                    __('messages.finished_repairs')
                )

                ->icon(
                    'heroicon-o-check-circle'
                )

                ->color('success'),

            /*
            |--------------------------------------------------------------------------
            | ACTIVE TICKETS
            |--------------------------------------------------------------------------
            */

            Stat::make(

                __('messages.active_tickets'),

                $activeTickets
            )

                ->description(
                    __('messages.repairs_in_progress')
                )

                ->icon(
                    'heroicon-o-wrench-screwdriver'
                )

                ->color('warning'),

            /*
            |--------------------------------------------------------------------------
            | AVERAGE REPAIR TIME
            |--------------------------------------------------------------------------
            */

            Stat::make(

                __('messages.average_repair_time'),

                round(
                    $averageRepairTime ?? 0,
                    2
                ) . ' h'

            )

                ->description(
                    __('messages.average_completion_time')
                )

                ->icon(
                    'heroicon-o-clock'
                )

                ->color('info'),

            /*
            |--------------------------------------------------------------------------
            | GENERATED REVENUE
            |--------------------------------------------------------------------------
            */

            Stat::make(

                __('messages.generated_revenue'),

                number_format(
                    $generatedRevenue,
                    2
                )

            )

                ->description(
                    __('messages.total_repair_revenue')
                )

                ->icon(
                    'heroicon-o-banknotes'
                )

                ->color('success'),

        ];
    }
}