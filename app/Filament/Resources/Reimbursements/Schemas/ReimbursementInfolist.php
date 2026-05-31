<?php

namespace App\Filament\Resources\Reimbursements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReimbursementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company_id')
                    ->numeric(),
                TextEntry::make('warranty_claim_id')
                    ->numeric(),
                TextEntry::make('supplier_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reference_number'),
                TextEntry::make('request_date')
                    ->date(),
                TextEntry::make('expected_payment_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('paid_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('requested_amount')
                    ->numeric(),
                TextEntry::make('approved_amount')
                    ->numeric(),
                TextEntry::make('paid_amount')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
