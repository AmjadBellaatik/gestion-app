<?php

namespace App\Filament\Resources\Reimbursements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReimbursementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('warranty_claim_id')
                    ->required()
                    ->numeric(),
                TextInput::make('supplier_id')
                    ->numeric(),
                TextInput::make('reference_number')
                    ->required(),
                DatePicker::make('request_date')
                    ->required(),
                DatePicker::make('expected_payment_date'),
                DatePicker::make('paid_date'),
                TextInput::make('requested_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('approved_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
