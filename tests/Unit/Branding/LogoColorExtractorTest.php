<?php

namespace Tests\Unit\Branding;

use App\Services\Branding\LogoColorExtractor;
use PHPUnit\Framework\TestCase;

/**
 * LogoColorExtractor — brand-colour detection from an uploaded logo.
 *
 * The extractor must surface the meaningful brand colours of a logo and
 * ignore the noise a real logo carries: a white/near-white canvas, a
 * transparent background, near-black anti-aliasing, and low-saturation grey.
 * Output is always #rrggbb (lowercase) or null when nothing usable is found.
 */
class LogoColorExtractorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('ext-gd is required for the colour extractor.');
        }

        $this->dir = sys_get_temp_dir() . '/logo-extractor-' . uniqid();
        @mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    /**
     * Paint vertical stripes of the given colours onto an opaque canvas.
     *
     * @param  array<int,array{0:int,1:int,2:int,3:float}>  $stripes  [r,g,b,widthFraction]
     */
    private function makePng(array $stripes, int $w = 240, int $h = 240, bool $transparentRest = false): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // Base canvas: opaque white, or fully transparent when requested.
        $base = $transparentRest
            ? imagecolorallocatealpha($img, 0, 0, 0, 127)
            : imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $w, $h, $base);

        $x = 0;
        foreach ($stripes as [$r, $g, $b, $frac]) {
            $stripeW = (int) round($w * $frac);
            $col = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, 0, $x + $stripeW, $h, $col);
            $x += $stripeW;
        }

        $path = $this->dir . '/' . uniqid('logo_') . '.png';
        imagepng($img, $path);

        return $path;
    }

    private function assertHex(string $value): void
    {
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $value, "Not a normalised hex colour: {$value}");
    }

    private function distance(string $hex, array $rgb): float
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return sqrt(($r - $rgb[0]) ** 2 + ($g - $rgb[1]) ** 2 + ($b - $rgb[2]) ** 2);
    }

    public function test_detects_dominant_brand_colours_over_a_white_background(): void
    {
        // 65% white canvas, 25% dark blue, 10% gold — the classic logo palette.
        $blue = [30, 58, 138];
        $gold = [212, 175, 55];
        $path = $this->makePng([
            [$blue[0], $blue[1], $blue[2], 0.25],
            [$gold[0], $gold[1], $gold[2], 0.10],
        ]);

        $palette = (new LogoColorExtractor)->extract($path);

        $this->assertNotNull($palette);
        $this->assertSame(['primary', 'secondary', 'accent'], array_keys($palette));
        $this->assertHex($palette['primary']);
        $this->assertHex($palette['secondary']);
        $this->assertHex($palette['accent']);

        // White must never win.
        $this->assertGreaterThan(40, $this->distance($palette['primary'], [255, 255, 255]));

        // The two meaningful colours must both appear in the palette.
        $found = array_map(
            fn (string $hex) => min($this->distance($hex, $blue), $this->distance($hex, $gold)),
            $palette
        );
        $this->assertLessThan(45, min($found), 'Neither brand colour was detected.');

        $near = fn (array $rgb) => min(array_map(fn ($h) => $this->distance($h, $rgb), $palette));
        $this->assertLessThan(45, $near($blue), 'Dark blue was not detected.');
        $this->assertLessThan(60, $near($gold), 'Gold was not detected.');
    }

    public function test_ignores_fully_transparent_pixels(): void
    {
        $red = [200, 30, 40];
        // Left 30% red, the rest fully transparent.
        $path = $this->makePng([[$red[0], $red[1], $red[2], 0.30]], transparentRest: true);

        $palette = (new LogoColorExtractor)->extract($path);

        $this->assertNotNull($palette);
        $this->assertLessThan(45, $this->distance($palette['primary'], $red));
    }

    public function test_near_white_background_does_not_dominate(): void
    {
        $teal = [13, 148, 136];
        // 88% #fdfdfd, 12% teal.
        $img = $this->makePng([[253, 253, 253, 0.88], [$teal[0], $teal[1], $teal[2], 0.12]]);

        $palette = (new LogoColorExtractor)->extract($img);

        $this->assertNotNull($palette);
        $this->assertLessThan(45, $this->distance($palette['primary'], $teal));
    }

    public function test_returns_null_for_a_blank_white_image(): void
    {
        $path = $this->makePng([]); // pure white

        $this->assertNull((new LogoColorExtractor)->extract($path));
    }

    public function test_returns_null_for_a_greyscale_only_image(): void
    {
        $path = $this->makePng([
            [40, 40, 40, 0.3],
            [128, 128, 128, 0.3],
            [200, 200, 200, 0.3],
        ]);

        $this->assertNull((new LogoColorExtractor)->extract($path));
    }

    public function test_returns_null_for_a_malformed_image(): void
    {
        $path = $this->dir . '/broken.png';
        file_put_contents($path, "\x89PNG\r\n\x1a\n" . str_repeat('not really png', 20));

        $this->assertNull((new LogoColorExtractor)->extract($path));
    }

    public function test_returns_null_for_a_missing_file(): void
    {
        $this->assertNull((new LogoColorExtractor)->extract($this->dir . '/does-not-exist.png'));
    }

    public function test_visually_duplicate_colours_are_collapsed(): void
    {
        $blue = [30, 58, 138];
        $blueish = [34, 62, 142]; // ~5 units away — the same brand colour
        $gold = [212, 175, 55];

        $path = $this->makePng([
            [$blue[0], $blue[1], $blue[2], 0.30],
            [$blueish[0], $blueish[1], $blueish[2], 0.25],
            [$gold[0], $gold[1], $gold[2], 0.15],
        ]);

        $palette = (new LogoColorExtractor)->extract($path);

        $this->assertNotNull($palette);
        // Secondary must be gold, not the near-duplicate blue.
        $this->assertLessThan(60, $this->distance($palette['secondary'], $gold));
        $this->assertGreaterThan(25, $this->distance($palette['secondary'], $blue));
    }

    public function test_all_returned_values_are_normalised_hex(): void
    {
        $path = $this->makePng([
            [220, 38, 38, 0.2],
            [37, 99, 235, 0.2],
            [22, 163, 74, 0.2],
        ]);

        $palette = (new LogoColorExtractor)->extract($path);

        $this->assertNotNull($palette);
        foreach ($palette as $hex) {
            $this->assertHex($hex);
        }
    }
}
