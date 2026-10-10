<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Container\Container;
use Override;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

abstract class UnitTestCase extends TestCase
{
    protected static ContainerInterface $container;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        self::$container = Container::getInstance();
    }
}
