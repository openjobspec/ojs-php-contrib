<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use OpenJobSpec\Client;
use OpenJobSpec\Job;
use OpenJobSpec\Testing\FakeTransport;

/**
 * @method static Job enqueue(string $type, array $args = [], array $options = [])
 * @method static Job[] enqueueBatch(array $jobs)
 * @method static Job getJob(string $jobId)
 * @method static Job cancel(string $jobId)
 * @method static array getQueues()
 * @method static \OpenJobSpec\QueueStats getQueueStats(string $queue)
 * @method static void pauseQueue(string $queue)
 * @method static void resumeQueue(string $queue)
 * @method static array health()
 * @method static array manifest()
 *
 * @see \OpenJobSpec\Client
 */
class Ojs extends Facade
{
    private static ?FakeTransport $fakeTransport = null;

    protected static function getFacadeAccessor(): string
    {
        return 'ojs';
    }

    /**
     * Enable fake mode for testing — replaces the real client with one
     * backed by an in-memory transport.
     */
    public static function fake(): FakeTransport
    {
        static::$fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => static::$fakeTransport]);
        static::swap($client);
        return static::$fakeTransport;
    }

    /**
     * Enqueue a job only after the current database transaction commits.
     */
    public static function enqueueAfterCommit(string $type, array $args = [], array $options = []): void
    {
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($type, $args, $options) {
            static::enqueue($type, $args, $options);
        });
    }

    // ── Test Assertions ─────────────────────────────────────

    public static function assertEnqueued(string $type, ?array $args = null, ?string $queue = null): void
    {
        if (static::$fakeTransport === null) {
            throw new \RuntimeException('Call Ojs::fake() before using assertions');
        }
        $found = static::$fakeTransport->allEnqueued($type, $queue);
        \PHPUnit\Framework\Assert::assertNotEmpty($found, "Expected job type '{$type}' to be enqueued");

        if ($args !== null) {
            $match = false;
            foreach ($found as $job) {
                if ($job->args === $args) {
                    $match = true;
                    break;
                }
            }
            \PHPUnit\Framework\Assert::assertTrue($match, "Expected job type '{$type}' enqueued with matching args");
        }
    }

    public static function refuteEnqueued(string $type, ?string $queue = null): void
    {
        if (static::$fakeTransport === null) {
            throw new \RuntimeException('Call Ojs::fake() before using assertions');
        }
        $found = static::$fakeTransport->allEnqueued($type, $queue);
        \PHPUnit\Framework\Assert::assertEmpty($found, "Expected job type '{$type}' NOT to be enqueued");
    }

    public static function assertEnqueuedCount(int $expected, ?string $type = null, ?string $queue = null): void
    {
        if (static::$fakeTransport === null) {
            throw new \RuntimeException('Call Ojs::fake() before using assertions');
        }
        $actual = static::$fakeTransport->enqueuedCount($type, $queue);
        \PHPUnit\Framework\Assert::assertEquals($expected, $actual, "Expected {$expected} enqueued jobs, got {$actual}");
    }

    /**
     * Reset fake state between tests.
     */
    public static function clearFake(): void
    {
        static::$fakeTransport?->clear();
    }
}
