<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;

use function Faslatam\PsrFramework\send_response;

require_once __DIR__ . '/vendor/autoload.php';

/** @var RequestHandlerInterface */
$app = require_once __DIR__ . '/bootstrap/app.php';
$request = ServerRequest::fromGlobals();
$response = $app->handle($request);

send_response($response);
