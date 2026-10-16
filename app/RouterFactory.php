<?php

declare(strict_types=1);

namespace App;

use Faslatam\PsrFramework\Router;
use NoDiscard;
use Psr\Container\ContainerInterface;

interface RouterFactory
{
    #[NoDiscard]
    public function __invoke(ContainerInterface $container): Router;
}
