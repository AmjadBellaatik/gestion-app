<?php

namespace App\Services\Branding;

/**
 * Detects the meaningful brand colours of a company logo.
 *
 * Pure PHP + ext-gd (already required by the runtime). No shell-outs, no
 * arbitrary filesystem access beyond the single path handed in, and every
 * failure mode — unreadable file, wrong type, truncated image, an image
 * with no usable colour — resolves to `null` instead of an exception, so a
 * bad upload can never break the Filament form.
 *
 * The result is always three normalised `#rrggbb` values keyed
 * primary / secondary / accent, or `null`.
 */
class LogoColorExtractor
{
    /** Hard ceiling on the source file — matches the FileUpload maxSize (10 MB). */
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** Longest edge the image is scaled down to before sampling. */
    private const SAMPLE_EDGE = 128;

    /** Channel bucket size for quantisation (0-255 → 11 buckets/channel). */
    private const BUCKET = 24;

    /** Alpha (0 opaque … 127 transparent) at or above which a pixel is ignored. */
    private const ALPHA_SKIP = 64;

    /** Euclidean RGB distance below which two colours are "the same brand colour". */
    private const DUPLICATE_DISTANCE = 60.0;

    private const ACCEPTED_MIME = ['image/png', 'image/jpeg', 'image/webp'];

    /**
     * @return array{primary:string,secondary:string,accent:string}|null
     */
    public function extract(string $absolutePath): ?array
    {
        try {
            $image = $this->readImage($absolutePath);

            if ($image === null) {
                return null;
            }

            $swatches = $this->rankColours($image);

            if ($swatches === []) {
                return null;
            }

            return $this->toPalette($swatches);
        } catch (\Throwable) {
            // Defence in depth: GD can emit warnings-as-throwables on odd input.
            return null;
        }
    }

    /* ----------------------------------------------------------------- */
    /*  Reading & validation                                            */
    /* ----------------------------------------------------------------- */

    private function readImage(string $path): ?\GdImage
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $bytes = filesize($path);
        if ($bytes === false || $bytes === 0 || $bytes > self::MAX_BYTES) {
            return null;
        }

        // Real content type, not the client-supplied extension.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : null;
        // finfo resources are freed automatically (finfo_close deprecated in PHP 8.5).
        if (! in_array($mime, self::ACCEPTED_MIME, true)) {
            return null;
        }

        // getimagesize also rejects a file whose header is not a real raster.
        $size = @getimagesize($path);
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $image = @imagecreatefromstring($raw);
        if (! $image instanceof \GdImage) {
            return null;
        }

        return $this->downscale($image);
    }

    private function downscale(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $longest = max($w, $h);

        if ($longest <= self::SAMPLE_EDGE) {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            return $image;
        }

        $scale = self::SAMPLE_EDGE / $longest;
        $scaled = imagescale($image, (int) round($w * $scale), (int) round($h * $scale));

        if (! $scaled instanceof \GdImage) {
            return $image;
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        return $scaled;
    }

    /* ----------------------------------------------------------------- */
    /*  Colour ranking                                                  */
    /* ----------------------------------------------------------------- */

    /**
     * @return array<int,array{rgb:array{0:int,1:int,2:int},score:float}>
     *   Meaningful colours, best first, visually-duplicate colours collapsed.
     */
    private function rankColours(\GdImage $image): array
    {
        $w = imagesx($image);
        $h = imagesy($image);

        /** @var array<string,array{r:int,g:int,b:int,count:int,weight:float}> $buckets */
        $buckets = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($image, $x, $y);

                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha >= self::ALPHA_SKIP) {
                    continue;
                }

                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                [$hue, $sat, $light] = $this->rgbToHsl($r, $g, $b);

                if (! $this->isMeaningful($sat, $light)) {
                    continue;
                }

                $key = intdiv($r, self::BUCKET)
                    . '-' . intdiv($g, self::BUCKET)
                    . '-' . intdiv($b, self::BUCKET);

                if (! isset($buckets[$key])) {
                    $buckets[$key] = ['r' => 0, 'g' => 0, 'b' => 0, 'count' => 0, 'weight' => 0.0];
                }

                // Perceptual weight: saturated, mid-bright pixels carry the brand.
                $brightness = 1.0 - (abs($light - 0.5) * 1.4);
                $weight = ($sat ** 1.5) * max($brightness, 0.15);

                $buckets[$key]['r'] += $r;
                $buckets[$key]['g'] += $g;
                $buckets[$key]['b'] += $b;
                $buckets[$key]['count']++;
                $buckets[$key]['weight'] += $weight;
            }
        }

        if ($buckets === []) {
            return [];
        }

        $totalPixels = max(1, $w * $h);

        $swatches = [];
        foreach ($buckets as $bucket) {
            $count = $bucket['count'];
            if ($count === 0) {
                continue;
            }

            $avg = [
                (int) round($bucket['r'] / $count),
                (int) round($bucket['g'] / $count),
                (int) round($bucket['b'] / $count),
            ];

            $frequency = $count / $totalPixels;

            // Frequency matters, but a small saturated mark beats a big pale wash.
            $score = ($bucket['weight'] / $count) * (0.35 + sqrt($frequency));

            $swatches[] = ['rgb' => $avg, 'score' => $score, 'count' => $count];
        }

        usort($swatches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $this->dedupe($swatches);
    }

    /**
     * A pixel is worth counting only if it is neither near-white, near-black,
     * nor washed-out grey — i.e. it actually carries hue information.
     */
    private function isMeaningful(float $sat, float $light): bool
    {
        if ($light >= 0.93) {
            return false; // near-white canvas
        }
        if ($light <= 0.06) {
            return false; // near-black ink / anti-aliasing
        }
        if ($sat < 0.14) {
            return false; // low-saturation grey
        }

        return true;
    }

    /**
     * @param  array<int,array{rgb:array{0:int,1:int,2:int},score:float,count:int}>  $swatches
     * @return array<int,array{rgb:array{0:int,1:int,2:int},score:float}>
     */
    private function dedupe(array $swatches): array
    {
        $kept = [];

        foreach ($swatches as $swatch) {
            foreach ($kept as $existing) {
                if ($this->distance($swatch['rgb'], $existing['rgb']) < self::DUPLICATE_DISTANCE) {
                    continue 2;
                }
            }
            $kept[] = $swatch;
        }

        return $kept;
    }

    /* ----------------------------------------------------------------- */
    /*  Palette assembly                                                */
    /* ----------------------------------------------------------------- */

    /**
     * @param  array<int,array{rgb:array{0:int,1:int,2:int},score:float}>  $swatches
     * @return array{primary:string,secondary:string,accent:string}
     */
    private function toPalette(array $swatches): array
    {
        $primary = $this->toHex($swatches[0]['rgb']);

        $secondary = isset($swatches[1])
            ? $this->toHex($swatches[1]['rgb'])
            : $this->shade($swatches[0]['rgb'], -0.35);

        $accent = isset($swatches[2])
            ? $this->toHex($swatches[2]['rgb'])
            : (isset($swatches[1])
                ? $this->shade($swatches[1]['rgb'], 0.2)
                : $this->shade($swatches[0]['rgb'], 0.25));

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
        ];
    }

    /** Lighten (amount > 0) or darken (amount < 0) an RGB triple, return hex. */
    private function shade(array $rgb, float $amount): string
    {
        $adjust = static function (int $channel) use ($amount): int {
            $target = $amount < 0 ? 0 : 255;
            $value = $channel + ($target - $channel) * abs($amount);

            return max(0, min(255, (int) round($value)));
        };

        return $this->toHex([$adjust($rgb[0]), $adjust($rgb[1]), $adjust($rgb[2])]);
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    private function distance(array $a, array $b): float
    {
        return sqrt(
            ($a[0] - $b[0]) ** 2
            + ($a[1] - $b[1]) ** 2
            + ($a[2] - $b[2]) ** 2
        );
    }

    /**
     * @return array{0:float,1:float,2:float}  [hue 0-360, saturation 0-1, lightness 0-1]
     */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $rf = $r / 255;
        $gf = $g / 255;
        $bf = $b / 255;

        $max = max($rf, $gf, $bf);
        $min = min($rf, $gf, $bf);
        $delta = $max - $min;

        $light = ($max + $min) / 2;

        if ($delta < 1e-9) {
            return [0.0, 0.0, $light];
        }

        $sat = $delta / (1 - abs(2 * $light - 1));

        $hue = match (true) {
            $max === $rf => 60 * fmod((($gf - $bf) / $delta), 6),
            $max === $gf => 60 * ((($bf - $rf) / $delta) + 2),
            default => 60 * ((($rf - $gf) / $delta) + 4),
        };

        if ($hue < 0) {
            $hue += 360;
        }

        return [$hue, $sat, $light];
    }
}
