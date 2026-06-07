{{--
    Motorcycle technical specifications table.

    Variables:
        $unit   - MotorcycleUnit model (eager-loaded with motorcycleModel.homologation)
        $model  - MotorcycleModel model
        $title  - (optional) override the card header text
--}}
@if(($unit ?? null) && ($model ?? null))
@php
    $wheelbase = collect([
        $model->empattement_1_2,
        $model->empattement_2_3,
        $model->empattement_3_4,
    ])->filter()->implode(' / ');

    $specs = [
        'MARQUE'                  => $model->marque,
        'Type'                    => $model->type,
        'Modele'                  => $model->modele,
        'Moteur (CC)'             => $model->cylindree,
        'Categorie'               => $model->categorie,
        'Alesage / Course'        => ($model->alesage && $model->course)
                                        ? $model->alesage . ' / ' . $model->course
                                        : null,
        'Cylindres'               => $model->nombre_cylindres,
        'Puissance Fiscale'       => $model->puissance_fiscale,
        'PAV Avant / Arriere'     => ($model->pav_avant || $model->pav_arriere)
                                        ? $model->pav_avant . ' / ' . $model->pav_arriere
                                        : null,
        'Poids a vide'            => $model->poids_vide_total,
        'PTC Avant / Arriere'     => ($model->ptc_avant || $model->ptc_arriere)
                                        ? $model->ptc_avant . ' / ' . $model->ptc_arriere
                                        : null,
        'PTAC'                    => $model->ptac,
        'Boite a vitesse'         => $model->boite_vitesse,
        'Empattement'             => $wheelbase ?: null,
        'Pneu AV / AR'            => ($model->pneu_avant || $model->pneu_arriere)
                                        ? $model->pneu_avant . ' / ' . $model->pneu_arriere
                                        : null,
        'Nombre de places'        => $model->nombre_places,
        'Carburant'               => $model->carburant,
        'N° Homologation'         => $model->titre_homologation
                                        ?: $model->homologation?->homologation_number,
        'N° Chassis'              => $unit->chassis_number,
    ];
@endphp

<div class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
    <div class="bg-slate-800 px-4 py-3">
        <h3 class="text-sm font-semibold text-white">
            {{ $title ?? (__('messages.motorcycle') . ' — ' . $model->marque . ' ' . $model->modele) }}
        </h3>
    </div>
    <table class="min-w-full text-sm">
        <tbody class="divide-y divide-slate-100 bg-white">
            @foreach($specs as $label => $value)
                @if($value !== null && $value !== '')
                    <tr class="hover:bg-slate-50">
                        <th class="w-2/5 bg-slate-50 px-4 py-2.5 text-left text-xs font-medium text-slate-600">
                            {{ $label }}
                        </th>
                        <td class="px-4 py-2.5 font-medium text-slate-900">{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endif
