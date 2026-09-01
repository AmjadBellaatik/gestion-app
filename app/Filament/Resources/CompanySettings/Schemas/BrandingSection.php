<?php

namespace App\Filament\Resources\CompanySettings\Schemas;

use App\Services\Branding\LogoColorExtractor;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * The shared "Visual Identity" section used by both the create and edit
 * company schemas.
 *
 * On logo upload (or replace) the brand colours are detected immediately,
 * client-side of the Save button, and pushed into the existing
 * primary/secondary/accent colour fields. The values are plain suggestions:
 * the pickers stay fully editable and a normal edit never re-runs
 * detection (it is bound only to the logo field). A compact live preview
 * reflects the current form values.
 */
class BrandingSection
{
    /** @var array<string,string> palette key => colour column */
    private const COLOR_FIELDS = [
        'primary' => 'primary_color',
        'secondary' => 'secondary_color',
        'accent' => 'accent_color',
    ];

    public static function make(): Section
    {
        return Section::make(__('messages.branding'))
            ->schema([
                FileUpload::make('logo')
                    ->label(__('messages.logo'))
                    ->disk('public')
                    ->directory('company/logo')
                    ->visibility('public')
                    ->acceptedFileTypes([
                        'image/png',
                        'image/jpeg',
                        'image/webp',
                        // SVG deliberately excluded: can embed scripts (stored XSS).
                    ])
                    ->image()
                    ->imagePreviewHeight('120')
                    ->maxSize(10240)
                    ->openable()
                    ->downloadable()
                    ->live()
                    ->afterStateUpdated(static function ($state, Set $set): void {
                        self::detectFromUpload($state, $set);
                    })
                    ->hintAction(
                        Action::make('detectColorsFromLogo')
                            ->label(__('messages.detect_colors_from_logo'))
                            ->icon('heroicon-m-swatch')
                            ->action(static function (Get $get, Set $set): void {
                                self::detectFromCurrentValue($get('logo'), $set);
                            })
                    )
                    ->columnSpanFull(),

                ColorPicker::make('primary_color')
                    ->label(__('messages.primary_color'))
                    ->hex()
                    ->live(onBlur: true)
                    ->default('#f59e0b'),

                ColorPicker::make('secondary_color')
                    ->label(__('messages.secondary_color'))
                    ->hex()
                    ->live(onBlur: true)
                    ->default('#111827'),

                ColorPicker::make('accent_color')
                    ->label(__('messages.accent_color'))
                    ->hex()
                    ->live(onBlur: true)
                    ->default('#2563eb'),

                Placeholder::make('branding_preview')
                    ->label(__('messages.visual_identity_preview'))
                    ->columnSpanFull()
                    ->content(static fn (Get $get): HtmlString => new HtmlString(
                        view('filament.company-branding-preview', [
                            'logoUrl' => self::previewLogoUrl($get('logo')),
                            'primary' => self::hexOr($get('primary_color'), '#f59e0b'),
                            'secondary' => self::hexOr($get('secondary_color'), '#111827'),
                            'accent' => self::hexOr($get('accent_color'), '#2563eb'),
                        ])->render()
                    )),
            ])
            ->columns(3);
    }

    /* ----------------------------------------------------------------- */

    private static function detectFromUpload(mixed $state, Set $set): void
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $path = (string) $file->getRealPath();
        } catch (\Throwable) {
            return;
        }

        self::applyPalette($path, $set);
    }

    private static function detectFromCurrentValue(mixed $state, Set $set): void
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        if ($file instanceof TemporaryUploadedFile) {
            self::detectFromUpload($file, $set);

            return;
        }

        if (is_string($file) && $file !== '' && Storage::disk('public')->exists($file)) {
            self::applyPalette(Storage::disk('public')->path($file), $set);

            return;
        }

        Notification::make()->title(__('messages.logo_analysis_failed'))->warning()->send();
    }

    private static function applyPalette(string $absolutePath, Set $set): void
    {
        $palette = app(LogoColorExtractor::class)->extract($absolutePath);

        if ($palette === null) {
            Notification::make()->title(__('messages.logo_analysis_failed'))->warning()->send();

            return;
        }

        foreach (self::COLOR_FIELDS as $key => $field) {
            $set($field, $palette[$key]);
        }

        Notification::make()->title(__('messages.colors_detected_from_logo'))->success()->send();
    }

    private static function previewLogoUrl(mixed $state): ?string
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        if ($file instanceof TemporaryUploadedFile) {
            try {
                return $file->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($file) && $file !== '') {
            return Storage::disk('public')->url($file);
        }

        return null;
    }

    private static function hexOr(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
            ? strtolower($value)
            : $fallback;
    }
}
