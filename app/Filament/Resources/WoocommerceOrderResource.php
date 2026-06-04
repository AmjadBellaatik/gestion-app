<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WoocommerceOrderResource\Pages;
use App\Models\WoocommerceOrder;

use Filament\Actions\ViewAction;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WoocommerceOrderResource extends Resource
{
    protected static ?string $model = WoocommerceOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.commercial');
    }

    // ── Labels ────────────────────────────────────────────────────────────────

    public static function getNavigationLabel(): string
    {
        return __('messages.woocommerce_orders');
    }

    public static function getModelLabel(): string
    {
        return __('messages.woocommerce_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.woocommerce_orders');
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordered_at', 'desc')
            ->columns([

                TextColumn::make('wc_order_number')
                    ->label(__('messages.order_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer_name')
                    ->label(__('messages.customer'))
                    ->getStateUsing(fn ($record) => trim($record->customer_first_name . ' ' . $record->customer_last_name))
                    ->searchable(query: fn ($query, string $value) => $query->where(function ($q) use ($value) {
                        $q->where('customer_first_name', 'like', "%{$value}%")
                          ->orWhere('customer_last_name',  'like', "%{$value}%")
                          ->orWhere('customer_email',      'like', "%{$value}%");
                    })),

                TextColumn::make('customer_email')
                    ->label(__('messages.email'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('customer_phone')
                    ->label(__('messages.phone'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed'  => 'success',
                        'processing' => 'warning',
                        'pending'    => 'info',
                        'on-hold'    => 'warning',
                        'cancelled', 'refunded', 'failed' => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('-', ' ', $state))),

                TextColumn::make('total')
                    ->label(__('messages.total'))
                    ->formatStateUsing(fn ($state, $record) => $record->currency . ' ' . number_format($state, 2))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('payment_method_title')
                    ->label(__('messages.payment_method'))
                    ->toggleable(),

                TextColumn::make('ordered_at')
                    ->label(__('messages.order_date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

            ])
            ->filters([

                SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'on-hold'    => 'On Hold',
                        'cancelled'  => 'Cancelled',
                        'refunded'   => 'Refunded',
                        'failed'     => 'Failed',
                    ]),

            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    // ── Infolist (View page) ──────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Order header ──────────────────────────────────────────────
                Section::make()->schema([
                    Grid::make(4)->schema([

                        TextEntry::make('wc_order_number')
                            ->label(__('messages.order_number'))
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed'  => 'success',
                                'processing' => 'warning',
                                'pending'    => 'info',
                                'on-hold'    => 'warning',
                                'cancelled', 'refunded', 'failed' => 'danger',
                                default      => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => ucfirst(str_replace('-', ' ', $state))),

                        TextEntry::make('ordered_at')
                            ->label(__('messages.order_date'))
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('payment_method_title')
                            ->label(__('messages.payment_method'))
                            ->placeholder('—'),

                    ]),
                ]),

                // ── Customer info ─────────────────────────────────────────────
                Section::make(__('messages.customer'))
                    ->columnSpan(1)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('customer_first_name')->label(__('messages.first_name')),
                            TextEntry::make('customer_last_name')->label(__('messages.last_name')),
                            TextEntry::make('customer_email')->label(__('messages.email'))->copyable(),
                            TextEntry::make('customer_phone')->label(__('messages.phone'))->placeholder('—'),
                        ]),
                    ]),

                // ── Billing address ───────────────────────────────────────────
                Section::make(__('messages.billing_address'))
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('billing')
                            ->label('')
                            ->getStateUsing(fn ($record) => self::formatAddress($record->billing))
                            ->html(),
                    ]),

                // ── Shipping address ──────────────────────────────────────────
                Section::make(__('messages.shipping_address'))
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('shipping')
                            ->label('')
                            ->getStateUsing(fn ($record) => self::formatAddress($record->shipping))
                            ->html(),
                    ]),

                // ── Order line items ──────────────────────────────────────────
                Section::make(__('messages.order_items'))
                    ->columnSpanFull()
                    ->schema([

                        RepeatableEntry::make('line_items')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label(__('messages.product')),
                                TextEntry::make('sku')->label('SKU')->placeholder('—'),
                                TextEntry::make('quantity')->label(__('messages.qty')),
                                TextEntry::make('price')
                                    ->label(__('messages.unit_price'))
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                                TextEntry::make('total')
                                    ->label(__('messages.total'))
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                                    ->weight('bold'),
                            ])
                            ->columns(5),

                        // Totals summary
                        Grid::make(3)->schema([
                            TextEntry::make('discount_total')
                                ->label(__('messages.discount'))
                                ->formatStateUsing(fn ($state, $record) => $record->currency . ' ' . number_format($state, 2)),
                            TextEntry::make('shipping_total')
                                ->label(__('messages.shipping'))
                                ->formatStateUsing(fn ($state, $record) => $record->currency . ' ' . number_format($state, 2)),
                            TextEntry::make('total')
                                ->label(__('messages.total'))
                                ->formatStateUsing(fn ($state, $record) => $record->currency . ' ' . number_format($state, 2))
                                ->weight('bold'),
                        ]),

                        // Customer note (only shown when present)
                        TextEntry::make('customer_note')
                            ->label(__('messages.customer_note'))
                            ->placeholder('—')
                            ->visible(fn ($record) => ! empty($record->customer_note)),

                    ]),

            ])
            ->columns(3);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function formatAddress(?array $address): string
    {
        if (empty($address)) {
            return '<span style="color:#9ca3af">—</span>';
        }

        $parts = array_filter([
            e(trim(($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? ''))),
            e($address['company']   ?? ''),
            e($address['address_1'] ?? ''),
            e($address['address_2'] ?? ''),
            e(trim(($address['city'] ?? '') . ' ' . ($address['postcode'] ?? ''))),
            e($address['state']   ?? ''),
            e($address['country'] ?? ''),
        ]);

        return implode('<br>', $parts) ?: '<span style="color:#9ca3af">—</span>';
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWoocommerceOrders::route('/'),
            'view'  => Pages\ViewWoocommerceOrder::route('/{record}'),
        ];
    }
}
