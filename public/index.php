<?php

declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad won't crash if .env is missing in prod

// 2. Build PHP-DI Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// 3. Create App with PHP-DI Bridge
$app = Bridge::create($container);

// 4. Register Middleware
$app->addRoutingMiddleware();

// Session Middleware
$app->add(\App\Middleware\SessionMiddleware::class);

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// Error Middleware (should be last). TODO: Configure details via ENV
$app->addErrorMiddleware(true, true, true); 

// 5. Register Routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 6. Run App
$app->run();
