<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Unit;

use Anderson\SteamGames\Support\UrlHelper;
use RuntimeException;

final class UrlHelperTest
{
    public function run(): void
    {
        $this->testBuildQueryEmpty();
        $this->testBuildQueryWithParams();
        $this->testBuildQueryFiltersNulls();
        $this->testBaseUrlRoot();
        $this->testBaseUrlSubdir();
    }

    private function testBuildQueryEmpty(): void
    {
        $this->assertSame('', UrlHelper::buildQuery([]), 'Empty params should return empty string');
    }

    private function testBuildQueryWithParams(): void
    {
        $params = ['a' => '1', 'b' => '2'];
        $this->assertSame('?a=1&b=2', UrlHelper::buildQuery($params), 'Params should be correctly encoded');
    }

    private function testBuildQueryFiltersNulls(): void
    {
        $params = ['a' => '1', 'b' => null, 'c' => ''];
        $this->assertSame('?a=1', UrlHelper::buildQuery($params), 'Null or empty string values should be filtered out');
    }

    private function testBaseUrlRoot(): void
    {
        $server = ['SCRIPT_NAME' => '/index.php'];
        $this->assertSame('/test', UrlHelper::base('test', $server), 'Base URL at root should be correct');
    }

    private function testBaseUrlSubdir(): void
    {
        $server = ['SCRIPT_NAME' => '/subfolder/index.php'];
        $this->assertSame('/subfolder/test', UrlHelper::base('test', $server), 'Base URL in subfolder should be correct');
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
        }
    }
}
