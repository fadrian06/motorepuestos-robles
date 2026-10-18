<?php

declare(strict_types=1);

use Twig\Environment;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var callable(): Environment */
$environmentFactory = require __DIR__ . '/view.php';

return $environmentFactory();
