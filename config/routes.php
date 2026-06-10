<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Public home placeholder
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write("PhotoGallery system is running.");
        return $response;
    })->setName('home');

    // Admin Auth Routes
    $app->get('/admin/login', [AuthController::class, 'showLogin'])->setName('admin.login');
    $app->post('/admin/login', [AuthController::class, 'processLogin'])->setName('admin.login.process');
    $app->post('/admin/logout', [AuthController::class, 'logout'])->setName('admin.logout');

    // Protected Admin Routes
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('', function (Request $request, Response $response) {
            // Render dashboard
            $twig = $this->get(\Slim\Views\Twig::class);
            return $twig->render($response, 'admin/dashboard.twig');
        })->setName('admin.dashboard');
    })->add(AuthMiddleware::class);
};
