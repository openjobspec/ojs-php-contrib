<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\OjsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OjsBundleTest extends TestCase
{
    public function testBundleExists(): void
    {
        $this->assertTrue(class_exists(OjsBundle::class));
    }

    public function testBundleExtendsAbstractBundle(): void
    {
        $bundle = new OjsBundle();
        $this->assertInstanceOf(AbstractBundle::class, $bundle);
    }

    public function testGetContainerExtensionReturnsExtension(): void
    {
        $bundle = new OjsBundle();
        $extension = $bundle->getContainerExtension();

        $this->assertNotNull($extension);
        $this->assertSame('ojs', $extension->getAlias());
    }
}
