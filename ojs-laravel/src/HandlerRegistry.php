<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel;

/**
 * Registry of OJS job handlers discovered from #[OjsJob] attributes.
 */
class HandlerRegistry
{
    /** @var array<string, callable> */
    private array $handlers = [];

    public function register(string $type, callable $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function get(string $type): ?callable
    {
        return $this->handlers[$type] ?? null;
    }

    /**
     * @return array<string, callable>
     */
    public function all(): array
    {
        return $this->handlers;
    }

    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }
}
