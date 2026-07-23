<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only migration (no schema change).
 *
 * Before this release, the client "nationality" Select passed a raw indexed
 * list (config('nationalities')) straight into Filament's ->options(), so the
 * VALUE actually persisted to clients.nationality was the array's numeric
 * POSITION (e.g. 106), not the slug ("moroccan"). The infolist then displayed
 * it correctly only because it looked the value back up in that exact same
 * array by position — a fragile coincidence.
 *
 * That underlying config/nationalities.php list has now been extended with
 * missing countries inserted alphabetically, which shifts most positions.
 * Any existing purely-numeric nationality value must be rewritten to the
 * slug it used to resolve to under the OLD (pre-extension) ordering — frozen
 * here — before the app starts reading nationality as a slug going forward.
 */
return new class extends Migration
{
    /** Exact config/nationalities.php order as it existed before this release. */
    private const LEGACY_ORDER = [
        'afghan', 'albanian', 'algerian', 'american', 'andorran', 'angolan', 'argentine',
        'armenian', 'australian', 'austrian', 'azerbaijani', 'bahamian', 'bahraini',
        'bangladeshi', 'barbadian', 'belarusian', 'belgian', 'belizean', 'beninese',
        'bhutanese', 'bolivian', 'bosnian', 'botswanan', 'brazilian', 'british', 'bruneian',
        'bulgarian', 'burkinabe', 'burundian', 'cambodian', 'cameroonian', 'canadian',
        'cape_verdean', 'central_african', 'chadian', 'chilean', 'chinese', 'colombian',
        'comorian', 'congolese', 'costa_rican', 'croatian', 'cuban', 'cypriot', 'czech',
        'danish', 'djiboutian', 'dominican', 'dutch', 'ecuadorean', 'egyptian', 'emirati',
        'equatorial_guinean', 'eritrean', 'estonian', 'ethiopian', 'fijian', 'finnish',
        'french', 'gabonese', 'gambian', 'georgian', 'german', 'ghanaian', 'greek',
        'guatemalan', 'guinean', 'haitian', 'honduran', 'hungarian', 'icelandic', 'indian',
        'indonesian', 'iranian', 'iraqi', 'irish', 'israeli', 'italian', 'ivorian',
        'jamaican', 'japanese', 'jordanian', 'kazakh', 'kenyan', 'kuwaiti', 'kyrgyz',
        'laotian', 'latvian', 'lebanese', 'liberian', 'libyan', 'lithuanian',
        'luxembourgish', 'macedonian', 'malagasy', 'malawian', 'malaysian', 'maldivian',
        'malian', 'maltese', 'mauritanian', 'mauritian', 'mexican', 'moldovan', 'mongolian',
        'montenegrin', 'moroccan', 'mozambican', 'namibian', 'nepalese', 'new_zealander',
        'nicaraguan', 'nigerian', 'norwegian', 'omani', 'pakistani', 'palestinian',
        'panamanian', 'paraguayan', 'peruvian', 'philippine', 'polish', 'portuguese',
        'qatari', 'romanian', 'russian', 'rwandan', 'saudi', 'senegalese', 'serbian',
        'singaporean', 'slovak', 'slovenian', 'somali', 'south_african', 'south_korean',
        'spanish', 'sri_lankan', 'sudanese', 'swedish', 'swiss', 'syrian', 'taiwanese',
        'tajik', 'tanzanian', 'thai', 'togolese', 'tunisian', 'turkish', 'ugandan',
        'ukrainian', 'uruguayan', 'uzbek', 'venezuelan', 'vietnamese', 'yemeni', 'zambian',
        'zimbabwean',
    ];

    public function up(): void
    {
        DB::table('clients')
            ->whereNotNull('nationality')
            ->whereRaw("nationality REGEXP '^[0-9]+$'")
            ->orderBy('id')
            ->select(['id', 'nationality'])
            ->get()
            ->each(function (object $row): void {
                $slug = self::LEGACY_ORDER[(int) $row->nationality] ?? null;

                DB::table('clients')
                    ->where('id', $row->id)
                    ->update(['nationality' => $slug]);
            });
    }

    public function down(): void
    {
        // Irreversible: the original numeric positions are not recoverable
        // once overwritten with slugs.
    }
};
