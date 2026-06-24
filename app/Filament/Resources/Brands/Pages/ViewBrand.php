<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Brands\BrandResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBrand extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = BrandResource::class;
}
