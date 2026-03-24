<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Unit;

use Anderson\SteamGames\Support\TextHelper;
use RuntimeException;

final class TextHelperTest
{
    public function run(): void
    {
        $this->testParsePrice();
        $this->testCleanDescription();
        $this->testParsePriceWithoutCurrency();
        $this->testCleanDescriptionTruncation();
    }

    private function testParsePrice(): void
    {
        $this->assertSame(0.0, TextHelper::parsePrice('Preço não disponível'), 'parsePrice should return 0 when price is unavailable');
        $this->assertSame(59.9, TextHelper::parsePrice('R$ 59,90'), 'parsePrice should parse BRL price');
    }

    private function testCleanDescription(): void
    {
        $raw = '<p>Jogo <strong>incrivel</strong> com   espaços.</p>';
        $clean = TextHelper::cleanDescription($raw, 120);
        $this->assertSame('Jogo incrivel com espaços.', $clean, 'cleanDescription should remove html and normalize spaces');
    }

    private function testParsePriceWithoutCurrency(): void
    {
        $this->assertSame(149.99, TextHelper::parsePrice('149,99'), 'parsePrice should support values without currency symbol');
    }

    private function testCleanDescriptionTruncation(): void
    {
        $raw = str_repeat('A', 50);
        $clean = TextHelper::cleanDescription($raw, 10);
        $this->assertSame('AAAAAAAAAA...', $clean, 'cleanDescription should truncate and append ellipsis');
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
        }
    }
}
