<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.document_verification') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-950">
    <div class="mx-auto max-w-5xl px-4 py-10">
        <div class="rounded-lg bg-white p-8 shadow">
            @if($authentic)
                @php
                    $unit = $document->items->first(fn ($item) => $item->motorcycleUnit)?->motorcycleUnit
                        ?: $document->primaryMotorcycleUnit();
                    $model = $unit?->motorcycleModel;
                    $homologation = $model?->homologation;
                    $company = $document->company;
                    $client = $document->client;
                    $companyName = $company?->name;
                    $brand = $model?->marque;
                    $constructor = $model?->usine_fabrication ?: $homologation?->manufacturer;
                    $accreditationReference = data_get($document->metadata, 'accreditation_reference');
                    $homologationNumber = $model?->titre_homologation ?: $homologation?->homologation_number;
                    $homologationDate = $model?->date_homologation?->format('d/m/Y')
                        ?: $homologation?->homologation_date?->format('d/m/Y');
                    $clientType = $client?->client_type;
                    $clientName = match ($clientType) {
                        'company' => $client?->company_name,
                        'administration' => $client?->administration_name,
                        default => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
                    };
                    $clientIdentity = $clientType === 'company'
                        ? $client?->rc
                        : ($clientType === 'administration' ? null : ($client?->rc ?: $client?->cin));
                    $capacity = $model?->cylindree ? $model->cylindree . ' CC' : null;
                    $power = trim($capacity . ($model?->puissance_effective ? ' / ' . $model->puissance_effective : ''));
                @endphp

                <div class="border-b border-slate-200 pb-6">
                    <div class="text-sm font-semibold uppercase tracking-wide text-green-700">
                        {{ __('messages.authentic_document') }}
                    </div>
                    <h1 class="mt-2 text-3xl font-bold">
                        {{ $document->documentType?->name }} {{ $document->document_number }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('messages.verification_url') }}: {{ $document->verification_url }}
                    </p>
                </div>

                @if($document->documentType?->code === 'CONFORMITY')
                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ __('messages.conformity_certificate_title') }}
                        </h2>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.conformity_constructor') }}</div>
                                <div class="font-bold">{{ $constructor }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.conformity_accreditation_reference') }}</div>
                                <div class="font-bold">{{ $accreditationReference }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.marque') }}</div>
                                <div class="font-bold">{{ $brand }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.homologation_number') }}</div>
                                <div class="font-bold">{{ $homologationNumber }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.homologation_date') }}</div>
                                <div class="font-bold">{{ $homologationDate }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                        </div>

                        <div class="mt-8 space-y-2 text-sm">
                            <div class="font-semibold">{{ __('messages.conformity_intro', ['company' => $companyName, 'brand' => $brand]) }}</div>
                            <div>{{ __('messages.conformity_mandataire', ['company' => $companyName]) }}</div>
                            <div>{{ __('messages.conformity_vehicle_intro') }}</div>
                        </div>

                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-slate-200">
                                    <tr><th class="w-1/3 bg-slate-50 p-3 text-left">{{ __('messages.marque') }}</th><td class="p-3 font-semibold">{{ $brand }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.genre') }}</th><td class="p-3 font-semibold">{{ $model?->genre }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.model') }}</th><td class="p-3 font-semibold">{{ $model?->modele }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.type') }}</th><td class="p-3 font-semibold">{{ $model?->type }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_category_label') }}</th><td class="p-3 font-semibold">{{ $model?->categorie }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_chassis_label') }}</th><td class="p-3 font-semibold">{{ $unit?->chassis_number }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_engine_power') }}</th><td class="p-3 font-semibold">{{ $power }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.fuel') }}</th><td class="p-3 font-semibold">{{ $model?->carburant }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_cylinder_label') }}</th><td class="p-3 font-semibold">{{ $model?->nombre_cylindres }}</td></tr>
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_client_name') }}</th><td class="p-3 font-semibold">{{ $clientName }}</td></tr>
                                    @if($clientType !== 'administration')
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.conformity_client_identity') }}</th><td class="p-3 font-semibold">{{ $clientIdentity }}</td></tr>
                                    @endif
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.address') }}</th><td class="p-3 font-semibold">{{ $client?->address }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm font-semibold">
                            <div>{{ __('messages.conformity_type_sentence') }}</div>
                            <div class="mt-2">{{ __('messages.conformity_homologation_sentence', ['number' => $homologationNumber, 'date' => $homologationDate]) }}</div>
                        </div>
                    </div>
                @elseif(in_array($document->documentType?->code, ['QUOTE', 'INVOICE'], true))
                    @php
                        $isQuote = $document->documentType?->code === 'QUOTE';
                        $manualClientType = data_get($document->metadata, 'manual_client_type', 'person');
                        $manualClientName = match ($manualClientType) {
                            'company' => data_get($document->metadata, 'manual_client_company_name'),
                            'administration' => data_get($document->metadata, 'manual_client_administration_name'),
                            default => trim(data_get($document->metadata, 'manual_client_first_name') . ' ' . data_get($document->metadata, 'manual_client_last_name')),
                        } ?: data_get($document->metadata, 'manual_client_name');
                        $displayClientName = $isQuote ? $manualClientName : $clientName;
                        $displayClientType = $isQuote ? $manualClientType : ($client?->client_type ?? 'person');
                        $displayClientPhone = $isQuote ? data_get($document->metadata, 'manual_client_phone') : $client?->phone;
                        $displayClientIce   = $isQuote ? data_get($document->metadata, 'manual_client_ice')   : $client?->ice;
                        $displayClientCin   = $isQuote ? data_get($document->metadata, 'manual_client_cin')   : $client?->cin;
                    @endphp

                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ $isQuote ? __('messages.quotation') : __('messages.invoice') }}
                        </h2>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.client') }}</div>
                                <div class="font-bold">{{ $displayClientName }}</div>
                                @if(in_array($displayClientType, ['company', 'administration']))
                                    @if($displayClientIce)<div class="text-sm">ICE: {{ $displayClientIce }}</div>@endif
                                    @if($displayClientPhone)<div class="text-sm">Tél: {{ $displayClientPhone }}</div>@endif
                                @else
                                    @if($displayClientCin)<div class="text-sm">CIN: {{ $displayClientCin }}</div>@endif
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                            @if(!$isQuote && data_get($document->metadata, 'purchase_order_number'))
                                <div>
                                    <div class="text-sm font-semibold text-slate-500">{{ __('messages.purchase_order') }}</div>
                                    <div class="font-bold">{{ data_get($document->metadata, 'purchase_order_number') }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="p-3 text-left">{{ __('messages.description') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.quantity') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.unit_price_ttc') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.fixed_discount') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($document->items as $item)
                                        <tr>
                                            <td class="p-3 font-semibold">{{ $item->description }}</td>
                                            <td class="p-3 text-right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                            <td class="p-3 text-right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                                            <td class="p-3 text-right">{{ number_format((float) $item->discount_amount, 2, ',', ' ') }} MAD</td>
                                            <td class="p-3 text-right font-bold">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 grid gap-3 md:grid-cols-3">
                            @php
                                $displayTotalTtc = (float) $document->total_amount;

                                if ($displayTotalTtc <= 0 && $document->items->isNotEmpty()) {
                                    $displayTotalTtc = (float) $document->items->sum(fn ($item) => (float) $item->total);
                                }

                                $displayTaxAmount = $displayTotalTtc > 0
                                    ? round($displayTotalTtc * (20 / 120), 2)
                                    : (float) $document->tax_amount;

                                $displaySubtotal = $displayTotalTtc > 0
                                    ? round($displayTotalTtc - $displayTaxAmount, 2)
                                    : (float) $document->subtotal;
                            @endphp
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.subtotal_ht') }}</div>
                                <div class="font-bold">{{ number_format($displaySubtotal, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.tva_20') }}</div>
                                <div class="font-bold">{{ number_format($displayTaxAmount, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.total_ttc') }}</div>
                                <div class="font-bold">{{ number_format($displayTotalTtc, 2, ',', ' ') }} MAD</div>
                            </div>
                        </div>

                        @foreach($document->items as $item)
                            @php
                                $qUnit = $item->motorcycleUnit;
                                $qModel = $qUnit?->motorcycleModel;
                                $qHomologation = $qModel?->homologation;
                                $qWheelbase = collect([$qModel?->empattement_1_2, $qModel?->empattement_2_3, $qModel?->empattement_3_4])->filter()->implode(' / ');
                            @endphp
                            @if($qUnit)
                                <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                                    <div class="bg-slate-900 p-3 font-bold text-white">{{ __('messages.motorcycle') }} - {{ $qModel?->marque }} {{ $qModel?->modele }}</div>
                                    <table class="w-full text-sm">
                                        <tbody class="divide-y divide-slate-200">
                                            <tr><th class="w-1/3 bg-slate-50 p-3 text-left">MARQUE</th><td class="p-3 font-semibold">{{ $qModel?->marque }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Type</th><td class="p-3 font-semibold">{{ $qModel?->type }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Modele</th><td class="p-3 font-semibold">{{ $qModel?->modele }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Moteur</th><td class="p-3 font-semibold">{{ $qModel?->cylindree }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Categorie</th><td class="p-3 font-semibold">{{ $qModel?->categorie }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Alesage / Course</th><td class="p-3 font-semibold">{{ $qModel?->alesage }} / {{ $qModel?->course }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Cylindre</th><td class="p-3 font-semibold">{{ $qModel?->nombre_cylindres }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Puissance Fiscale</th><td class="p-3 font-semibold">{{ $qModel?->puissance_fiscale }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">PAV Avant / Arriere</th><td class="p-3 font-semibold">{{ $qModel?->pav_avant }} / {{ $qModel?->pav_arriere }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Poids a vide total</th><td class="p-3 font-semibold">{{ $qModel?->poids_vide_total }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">PTC Avant / Arriere</th><td class="p-3 font-semibold">{{ $qModel?->ptc_avant }} / {{ $qModel?->ptc_arriere }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">PTAC</th><td class="p-3 font-semibold">{{ $qModel?->ptac }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Boite a vitesse</th><td class="p-3 font-semibold">{{ $qModel?->boite_vitesse }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Empattement</th><td class="p-3 font-semibold">{{ $qWheelbase }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Pneu tubeless AV / AR</th><td class="p-3 font-semibold">{{ $qModel?->pneu_avant }} / {{ $qModel?->pneu_arriere }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Nombre de places assise</th><td class="p-3 font-semibold">{{ $qModel?->nombre_places }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Carburant</th><td class="p-3 font-semibold">{{ $qModel?->carburant }}</td></tr>
                                            <tr><th class="bg-slate-50 p-3 text-left">Homologation</th><td class="p-3 font-semibold">{{ $qModel?->titre_homologation ?: $qHomologation?->homologation_number }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @elseif($document->documentType?->code === 'WARRANTY_CONTRACT')
                    @php
                        $productItem = $document->items->first(fn ($item) => $item->product);
                        $product = $productItem?->product;
                        $warrantyDurationValue = data_get($document->metadata, 'warranty_duration_value')
                            ?: data_get($document->metadata, 'warranty_years');
                        $warrantyDurationUnit = data_get($document->metadata, 'warranty_duration_unit', 'years');
                        $warrantyDurationLabel = trim($warrantyDurationValue . ' ' . __('messages.' . $warrantyDurationUnit));
                        $warrantyKilometers = data_get($document->metadata, 'warranty_kilometers');
                        $coveredItemName = $unit
                            ? trim(($model?->marque ? $model->marque . ' ' : '') . ($model?->modele ?: ''))
                            : $product?->name;
                        $coveredItemType = $unit ? $model?->type : ($product?->type ? __('messages.' . $product->type) : null);
                        $coveredItemReference = $unit ? $unit?->chassis_number : ($product?->sku ?: $product?->barcode);
                    @endphp

                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ __('messages.warranty_contract') }}
                        </h2>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.client') }}</div>
                                <div class="font-bold">{{ $clientName }}</div>
                            </div>
                            @if($clientIdentity)
                                <div>
                                    <div class="text-sm font-semibold text-slate-500">{{ __('messages.cin') }} / {{ __('messages.rc') }}</div>
                                    <div class="font-bold">{{ $clientIdentity }}</div>
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.warranty_duration') }}</div>
                                <div class="font-bold">{{ $warrantyDurationLabel }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.warranty_distance') }}</div>
                                <div class="font-bold">{{ $warrantyKilometers }} KM</div>
                            </div>
                        </div>

                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-slate-200">
                                    @if($unit)
                                        <tr><th class="w-1/3 bg-slate-50 p-3 text-left">{{ __('messages.marque') }}</th><td class="p-3 font-semibold">{{ $model?->marque }}</td></tr>
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.model') }}</th><td class="p-3 font-semibold">{{ $model?->modele }}</td></tr>
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.type') }}</th><td class="p-3 font-semibold">{{ $model?->type }}</td></tr>
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.chassis_number') }}</th><td class="p-3 font-semibold">{{ $unit?->chassis_number }}</td></tr>
                                    @else
                                        <tr><th class="w-1/3 bg-slate-50 p-3 text-left">{{ $coveredItemType }}</th><td class="p-3 font-semibold">{{ $coveredItemName }}</td></tr>
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.type') }}</th><td class="p-3 font-semibold">{{ $coveredItemType }}</td></tr>
                                        <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.sku') }} / {{ __('messages.barcode') }}</th><td class="p-3 font-semibold">{{ $coveredItemReference }}</td></tr>
                                    @endif
                                    <tr><th class="bg-slate-50 p-3 text-left">{{ __('messages.address') }}</th><td class="p-3 font-semibold">{{ $client?->address }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                            SOCIETE <strong>{{ $companyName }}</strong> s'engage de donner
                            <strong>{{ $warrantyDurationLabel }}</strong> de garantie ou
                            <strong>{{ $warrantyKilometers }}</strong> KM a compter de la date de livraison.
                        </div>
                    </div>
                @elseif($document->documentType?->code === 'DELIVERY_NOTE')
                    @php
                        $dlClientName = match ($clientType) {
                            'company'        => $client?->company_name,
                            'administration' => $client?->administration_name,
                            default          => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
                        };
                        $dlTotalTtc = (float) $document->total_amount;
                        if ($dlTotalTtc <= 0 && $document->items->isNotEmpty()) {
                            $dlTotalTtc = (float) $document->items->sum(fn ($i) => (float) $i->total);
                        }
                        $dlTaxAmount = $dlTotalTtc > 0 ? round($dlTotalTtc * (20 / 120), 2) : (float) $document->tax_amount;
                        $dlSubtotal  = $dlTotalTtc > 0 ? round($dlTotalTtc - $dlTaxAmount, 2) : (float) $document->subtotal;
                    @endphp

                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ __('messages.delivery_note') }}
                        </h2>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.client') }}</div>
                                <div class="font-bold">{{ $dlClientName }}</div>
                                @if(in_array($clientType, ['company', 'administration']))
                                    @if($client?->ice)<div class="text-sm">ICE: {{ $client->ice }}</div>@endif
                                    @if($client?->phone)<div class="text-sm">Tél: {{ $client->phone }}</div>@endif
                                @else
                                    @if($client?->cin)<div class="text-sm">CIN: {{ $client->cin }}</div>@endif
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                            @if($document->sale)
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.sale') }}</div>
                                <div class="font-bold">{{ $document->sale?->sale_number }}</div>
                            </div>
                            @endif
                        </div>

                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="p-3 text-left">{{ __('messages.description') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.quantity') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.unit_price_ttc') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($document->items as $item)
                                    <tr>
                                        <td class="p-3">
                                            <div class="font-semibold">{{ $item->description }}</div>
                                            @if($item->motorcycleUnit)
                                                <div class="text-xs text-slate-500">{{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}</div>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                                        <td class="p-3 text-right font-bold">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.subtotal_ht') }}</div>
                                <div class="font-bold">{{ number_format($dlSubtotal, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.tva_20') }}</div>
                                <div class="font-bold">{{ number_format($dlTaxAmount, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.total_ttc') }}</div>
                                <div class="font-bold">{{ number_format($dlTotalTtc, 2, ',', ' ') }} MAD</div>
                            </div>
                        </div>
                    </div>
                @elseif($document->documentType?->code === 'SALE_RETURN')
                    @php
                        $retClientType = $client?->client_type;
                        $retClientName = match ($retClientType) {
                            'company'        => $client?->company_name,
                            'administration' => $client?->administration_name,
                            default          => trim(($client?->first_name ?? '') . ' ' . ($client?->last_name ?? '')),
                        };
                        $retTotalTtc = (float) $document->total_amount;
                        if ($retTotalTtc <= 0 && $document->items->isNotEmpty()) {
                            $retTotalTtc = (float) $document->items->sum(fn ($i) => (float) $i->total);
                        }
                        $retTaxAmount = $retTotalTtc > 0 ? round($retTotalTtc * (20 / 120), 2) : (float) $document->tax_amount;
                        $retSubtotal  = $retTotalTtc > 0 ? round($retTotalTtc - $retTaxAmount, 2) : (float) $document->subtotal;
                    @endphp

                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ __('messages.sale_return') }}
                        </h2>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                                @if($company?->address)<div class="text-sm">{{ $company->address }}</div>@endif
                                @if($company?->phone)<div class="text-sm">{{ $company->phone }}</div>@endif
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.client') }}</div>
                                <div class="font-bold">{{ $retClientName }}</div>
                                @if(in_array($retClientType, ['company', 'administration']))
                                    @if($client?->ice)<div class="text-sm">ICE: {{ $client->ice }}</div>@endif
                                    @if($client?->phone)<div class="text-sm">Tél: {{ $client->phone }}</div>@endif
                                @else
                                    @if($client?->cin)<div class="text-sm">CIN: {{ $client->cin }}</div>@endif
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_number') }}</div>
                                <div class="font-bold">{{ $document->document_number }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                            @if($document->sale)
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.sale') }}</div>
                                <div class="font-bold">{{ $document->sale->sale_number }}</div>
                            </div>
                            @endif
                        </div>

                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="p-3 text-left">{{ __('messages.description') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.quantity') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.unit_price_ttc') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.tax_rate') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($document->items as $item)
                                    <tr>
                                        <td class="p-3">
                                            <div class="font-semibold">{{ $item->description }}</div>
                                            @if($item->motorcycleUnit)
                                                <div class="text-xs text-slate-500">{{ __('messages.chassis_number') }}: {{ $item->motorcycleUnit->chassis_number }}</div>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                                        <td class="p-3 text-right">20%</td>
                                        <td class="p-3 text-right font-bold">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.subtotal_ht') }}</div>
                                <div class="font-bold">{{ number_format($retSubtotal, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.tva_20') }}</div>
                                <div class="font-bold">{{ number_format($retTaxAmount, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.total_ttc') }}</div>
                                <div class="font-bold">{{ number_format($retTotalTtc, 2, ',', ' ') }} MAD</div>
                            </div>
                        </div>

                        @if($document->notes)
                        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                            <div class="font-semibold text-slate-600">{{ __('messages.return_reason') }}</div>
                            <div class="mt-1">{{ $document->notes }}</div>
                        </div>
                        @endif
                    </div>
                @else
                    <div class="mt-8 rounded-lg border border-slate-200 p-6">
                        <h2 class="text-center text-2xl font-bold underline">
                            {{ $document->documentType?->name ?? __('messages.document') }}
                        </h2>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.company') }}</div>
                                <div class="font-bold">{{ $companyName }}</div>
                            </div>
                            @if($client)
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.client') }}</div>
                                <div class="font-bold">{{ $clientName }}</div>
                                @if(in_array($clientType, ['company', 'administration']))
                                    @if($client?->ice)<div class="text-sm">ICE: {{ $client->ice }}</div>@endif
                                    @if($client?->phone)<div class="text-sm">Tél: {{ $client->phone }}</div>@endif
                                @else
                                    @if($client?->cin)<div class="text-sm">CIN: {{ $client->cin }}</div>@endif
                                @endif
                            </div>
                            @endif
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_number') }}</div>
                                <div class="font-bold">{{ $document->document_number }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.document_date') }}</div>
                                <div class="font-bold">{{ $document->document_date?->format('d/m/Y') }}</div>
                            </div>
                        </div>

                        @if($document->items->isNotEmpty())
                        <div class="mt-8 overflow-hidden rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="p-3 text-left">{{ __('messages.description') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.quantity') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.unit_price_ttc') }}</th>
                                        <th class="p-3 text-right">{{ __('messages.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($document->items as $item)
                                    <tr>
                                        <td class="p-3 font-semibold">{{ $item->description }}</td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                        <td class="p-3 text-right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                                        <td class="p-3 text-right font-bold">{{ number_format((float) $item->total, 2, ',', ' ') }} MAD</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @php
                            $fallTotalTtc = (float) $document->total_amount;
                            if ($fallTotalTtc <= 0 && $document->items->isNotEmpty()) {
                                $fallTotalTtc = (float) $document->items->sum(fn ($i) => (float) $i->total);
                            }
                            $fallTaxAmount = $fallTotalTtc > 0 ? round($fallTotalTtc * (20 / 120), 2) : (float) $document->tax_amount;
                            $fallSubtotal  = $fallTotalTtc > 0 ? round($fallTotalTtc - $fallTaxAmount, 2) : (float) $document->subtotal;
                        @endphp
                        <div class="mt-6 grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.subtotal_ht') }}</div>
                                <div class="font-bold">{{ number_format($fallSubtotal, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.tva_20') }}</div>
                                <div class="font-bold">{{ number_format($fallTaxAmount, 2, ',', ' ') }} MAD</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-500">{{ __('messages.total_ttc') }}</div>
                                <div class="font-bold">{{ number_format($fallTotalTtc, 2, ',', ' ') }} MAD</div>
                            </div>
                        </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-red-600">
                        {{ __('messages.not_authentic_document') }}
                    </h1>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
