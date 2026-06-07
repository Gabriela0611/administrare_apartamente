<?php

declare(strict_types=1);

final class TestRunner
{
    /** @var array<int, array{name: string, fn: callable}> */
    private static array $tests = [];
    private static int $passed = 0;
    /** @var string[] */
    private static array $failures = [];

    public static function add(string $name, callable $fn): void
    {
        self::$tests[] = ['name' => $name, 'fn' => $fn];
    }

    public static function run(): int
    {
        foreach (self::$tests as $test) {
            try {
                ($test['fn'])();
                self::$passed++;
                fwrite(STDOUT, "  PASS  {$test['name']}\n");
            } catch (AssertionFailed $e) {
                self::$failures[] = "{$test['name']}: {$e->getMessage()}";
                fwrite(STDOUT, "  FAIL  {$test['name']} -- {$e->getMessage()}\n");
            } catch (\Throwable $e) {
                self::$failures[] = "{$test['name']}: unexpected " . $e::class . ' - ' . $e->getMessage();
                fwrite(STDOUT, "  ERROR {$test['name']} -- {$e->getMessage()}\n");
            }
        }

        $total = self::$passed + count(self::$failures);
        fwrite(STDOUT, "\n{$total} test(s), " . self::$passed . ' passed, ' . count(self::$failures) . " failed.\n");

        return self::$failures === [] ? 0 : 1;
    }
}

final class AssertionFailed extends \RuntimeException
{
}

function test(string $name, callable $fn): void
{
    TestRunner::add($name, $fn);
}

function assert_true(bool $condition, string $message = 'Expected true'): void
{
    if ($condition !== true) {
        throw new AssertionFailed($message);
    }
}

function assert_false(bool $condition, string $message = 'Expected false'): void
{
    if ($condition !== false) {
        throw new AssertionFailed($message);
    }
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $detail = sprintf(
            'expected %s but got %s',
            var_export($expected, true),
            var_export($actual, true)
        );
        throw new AssertionFailed(trim($message . ' ' . $detail));
    }
}
