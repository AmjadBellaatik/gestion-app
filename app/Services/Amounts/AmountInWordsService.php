<?php

namespace App\Services\Amounts;

use NumberToWords\NumberToWords;

class AmountInWordsService
{
    public static function convert(
        float $amount,
        string $language = 'fr'
    ): string {

        $numberToWords =
            new NumberToWords();

        $integerPart =
            (int) floor($amount);

        switch ($language) {

            case 'ar':

                $transformer =
                    $numberToWords
                        ->getNumberTransformer(
                            'ar'
                        );

                $currency =
                    'درهم';

                break;

            case 'en':

                $transformer =
                    $numberToWords
                        ->getNumberTransformer(
                            'en'
                        );

                $currency =
                    'dirhams';

                break;

            case 'fr':

            default:

                $transformer =
                    $numberToWords
                        ->getNumberTransformer(
                            'fr'
                        );

                $currency =
                    'dirhams';

                break;
        }

        $words =
            $transformer->toWords(
                $integerPart
            );

        return ucfirst(

            trim(
                $words . ' ' . $currency
            )

        );
    }
}