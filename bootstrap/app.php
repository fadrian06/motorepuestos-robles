<?php

declare(strict_types=1);

use App\Http\Handlers\NotFound;
use Faslatam\PsrFramework\QueueRequestHandler;
use GuzzleHttp\Psr7\HttpFactory;

$responseFactory = new HttpFactory();

return new QueueRequestHandler(new NotFound($responseFactory));
