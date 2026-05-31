<!DOCTYPE html>
<html
    lang="{{ $document->language }}"
    dir="{{ $document->language == 'ar' ? 'rtl' : 'ltr' }}"
>

<head>

    <meta charset="utf-8">

    <style>

        body {

            font-family: DejaVu Sans;
            font-size: 13px;
            line-height: 1.7;

        }

        .title {

            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 30px;
            text-decoration: underline;

        }

        .section {

            margin-bottom: 12px;

        }

        .label {

            font-weight: bold;

        }

        .signature {

            margin-top: 80px;
            text-align: right;

        }

        .footer {

            margin-top: 60px;
            text-align: center;
            font-size: 11px;

        }

    </style>

</head>

<body>

    {{-- COMPANY HEADER --}}

    <table width="100%">

        <tr>

            <td width="30%">

                @if($company->logo)

                    <img
                        src="{{ public_path('storage/' . $company->logo) }}"
                        height="90"
                        style="max-width: 90px; object-fit: contain;"
                    >

                @endif

            </td>

            <td width="70%" align="right">

                <strong>
                    {{ $company->name }}
                </strong>

                <br>

                ICE :
                {{ $company->ice }}

                <br>

                RC :
                {{ $company->rc }}

                <br>

                IF :
                {{ $company->if }}

            </td>

        </tr>

    </table>

    <br><br>

    <div class="title">

        CERTIFICAT DE CONFORMITE

    </div>

    <div class="section">

        Nous soussignés,

        <strong>
            {{ $company->name }}
        </strong>

        mandataire dûment accrédité de la marque

        <strong>
            {{ $motorcycle->brand ?? 'GALAXI' }}
        </strong>

        certifions que :

    </div>

    <div class="section">

        <span class="label">
            Constructeur :
        </span>

        {{ $motorcycle->manufacturer ?? 'CHONGQING' }}

    </div>

    <div class="section">

        <span class="label">
            Mandataire dûment accrédité :
        </span>

        {{ $company->name }}

    </div>

    <div class="section">

        <span class="label">
            N° ALM :
        </span>

        {{ $motorcycle->alm_number ?? 'ALM99' }}

    </div>

    <div class="section">

        <span class="label">
            Marque :
        </span>

        {{ $motorcycle->brand ?? 'GALAXI' }}

    </div>

    <div class="section">

        <span class="label">
            Genre :
        </span>

        {{ $motorcycle->genre ?? 'CYCLOMOTEUR' }}

    </div>

    <div class="section">

        <span class="label">
            Modèle :
        </span>

        {{ $motorcycle->model ?? '' }}

    </div>

    <div class="section">

        <span class="label">
            Type :
        </span>

        {{ $motorcycle->type ?? '' }}

    </div>

    <div class="section">

        <span class="label">
            Catégorie :
        </span>

        {{ $motorcycle->category ?? 'L1' }}

    </div>

    <div class="section">

        <span class="label">
            N° de châssis :
        </span>

        {{ $motorcycle->chassis_number }}

    </div>

    <div class="section">

        <span class="label">
            Cylindrée & puissance :
        </span>

        {{ $motorcycle->engine_capacity ?? '' }}

    </div>

    <div class="section">

        <span class="label">
            Carburant :
        </span>

        {{ $motorcycle->fuel ?? 'essence' }}

    </div>

    <div class="section">

        <span class="label">
            Nombre de cylindre :
        </span>

        {{ $motorcycle->cylinders ?? 1 }}

    </div>

    <div class="section">

        Est entièrement conforme au type dont le prototype
        a fait l'objet du procès-verbal d'homologation.

    </div>

    <div class="section">

        <span class="label">
            Homologation :
        </span>

        {{ $motorcycle->homologation ?? '' }}

    </div>

    <br>

    <div class="section">

        <span class="label">
            Nom ou raison sociale :
        </span>

        {{ $client->name ?? '' }}

    </div>

    <div class="section">

        <span class="label">
            N° RC / CIN :
        </span>

        {{ $client->cin ?? '' }}

    </div>

    <div class="section">

        <span class="label">
            Adresse :
        </span>

        {{ $client->address ?? '' }}

    </div>

    <div class="signature">

        Fait à

        {{ $company->city ?? 'SALE' }}

        le :

        {{ now()->format('d/m/Y') }}

        <br><br>

        Signature et cachet du constructeur
        ou de son mandataire au Maroc

        <br><br>

    </div>

    <div class="footer">

        @if($company->footer)

            {!! $company->footer !!}

        @endif

    </div>

    <div
        style="
            margin-top: 40px;
            text-align: right;
        "
    >

        <div
            style="
                font-size: 12px;
                margin-bottom: 10px;
            "
        >

            {{ __('messages.verify_document') }}

        </div>

        {!! QrCode::size(100)->generate(
            $document->verification_url
        ) !!}

    </div>

</body>
</html>
