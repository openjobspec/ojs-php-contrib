<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony;

use OpenJobSpec\Symfony\DependencyInjection\OjsExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OjsBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OjsExtension();
    }
}
