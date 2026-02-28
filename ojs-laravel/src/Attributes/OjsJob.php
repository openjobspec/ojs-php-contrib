<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Attributes;

use Attribute;

/**
 * Mark a class as an OJS job handler.
 *
 * Usage:
 *   #[OjsJob('email.send', queue: 'emails')]
 *   class SendEmailHandler {
 *       public function __invoke(JobContext $ctx): mixed { ... }
 *   }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class OjsJob
{
    public function __construct(
        public readonly string $type,
        public readonly string $queue = 'default',
        public readonly ?int $priority = null,
        public readonly ?int $timeout = null,
    ) {}
}
