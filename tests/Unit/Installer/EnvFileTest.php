<?php

namespace Tests\Unit\Installer;

use App\Support\EnvFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'env');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function seed(string $contents): EnvFile
    {
        file_put_contents($this->path, $contents);

        return new EnvFile($this->path);
    }

    #[Test]
    public function it_updates_an_existing_key_in_place_and_preserves_everything_else(): void
    {
        $env = $this->seed(<<<ENV
        # comment
        APP_NAME="Old Name"
        APP_ENV=local

        DB_HOST=127.0.0.1
        DB_PASSWORD=oldsecret
        ENV);

        $env->write(['DB_PASSWORD' => 'n3w', 'APP_ENV' => 'production']);

        $out = file_get_contents($this->path);
        $this->assertStringContainsString('# comment', $out);
        $this->assertStringContainsString('APP_NAME="Old Name"', $out);
        $this->assertStringContainsString('APP_ENV=production', $out);
        $this->assertStringContainsString('DB_PASSWORD=n3w', $out);
        $this->assertStringContainsString("\n\nDB_HOST=127.0.0.1", $out); // blank line kept
    }

    #[Test]
    public function it_appends_absent_keys(): void
    {
        $env = $this->seed("APP_ENV=local\n");
        $env->write(['DB_DATABASE' => 'shop']);

        $this->assertSame('shop', $env->get('DB_DATABASE'));
        $this->assertStringContainsString("APP_ENV=local\nDB_DATABASE=shop\n", file_get_contents($this->path));
    }

    #[Test]
    public function it_quotes_values_that_need_it(): void
    {
        $env = $this->seed("A=1\n");
        $env->write([
            'SPACE' => 'two words',
            'HASH' => 'a#b',
            'EMPTY' => '',
            'PLAIN' => 'plain',
            'EQ' => 'a=b',
        ]);

        $out = file_get_contents($this->path);
        $this->assertStringContainsString('SPACE="two words"', $out);
        $this->assertStringContainsString('HASH="a#b"', $out);
        $this->assertStringContainsString('EMPTY=""', $out);
        $this->assertStringContainsString("\nPLAIN=plain\n", $out);
        $this->assertStringContainsString('EQ="a=b"', $out);

        // Round-trips back to the original raw value.
        $this->assertSame('a#b', $env->get('HASH'));
        $this->assertSame('', $env->get('EMPTY'));
        $this->assertSame('a=b', $env->get('EQ'));
    }

    #[Test]
    public function it_reads_quoted_and_inline_comment_values(): void
    {
        $env = $this->seed("A=\"quoted value\"\nB=bare # trailing comment\nC='single quoted'\n");

        $this->assertSame('quoted value', $env->get('A'));
        $this->assertSame('bare', $env->get('B'));
        $this->assertSame('single quoted', $env->get('C'));
    }

    #[Test]
    public function it_normalises_crlf_and_collapses_duplicate_keys(): void
    {
        $env = $this->seed("APP_ENV=local\r\nFOO=1\r\nAPP_ENV=staging\r\n");
        $env->write(['APP_ENV' => 'production']);

        $out = file_get_contents($this->path);
        $this->assertStringNotContainsString("\r", $out);
        $this->assertSame(1, substr_count($out, 'APP_ENV='));
        $this->assertStringContainsString('APP_ENV=production', $out);
        $this->assertStringContainsString("FOO=1", $out);
    }

    #[Test]
    public function it_can_remove_a_key_with_null(): void
    {
        $env = $this->seed("KEEP=1\nDROP=2\n");
        $env->write(['DROP' => null]);

        $this->assertFalse($env->has('DROP'));
        $this->assertTrue($env->has('KEEP'));
    }

    #[Test]
    public function it_preserves_an_empty_password_line(): void
    {
        $env = $this->seed("DB_PASSWORD=\nOTHER=x\n");
        $env->write(['DB_USERNAME' => 'root']);

        $this->assertTrue($env->has('DB_PASSWORD'));
        $this->assertSame('', $env->get('DB_PASSWORD'));
        $this->assertSame('root', $env->get('DB_USERNAME'));
    }

    #[Test]
    public function it_writes_booleans_as_words(): void
    {
        $env = $this->seed("APP_DEBUG=true\n");
        $env->write(['APP_DEBUG' => false]);

        $this->assertStringContainsString('APP_DEBUG=false', file_get_contents($this->path));
    }

    #[Test]
    public function it_handles_export_prefand_leading_whitespace_keys(): void
    {
        $env = $this->seed("  DB_HOST=1.2.3.4\nexport DB_PORT=3307\n");
        $env->write(['DB_HOST' => '10.0.0.1', 'DB_PORT' => '3306']);

        $this->assertSame('10.0.0.1', $env->get('DB_HOST'));
        $this->assertSame('3306', $env->get('DB_PORT'));
    }
}
