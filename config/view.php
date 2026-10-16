<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return static function (): Environment {
    $environment = new Environment(new FilesystemLoader([
        __DIR__ . '/../resources/views',
    ]));

    $environment->addGlobal('_ENV', $_ENV);

    return $environment;
};
