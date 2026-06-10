<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            // Not authenticated, redirect to login
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $loginUrl = $routeParser->urlFor('admin.login');
            
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', $loginUrl)->withStatus(302);
        }

        return $handler->handle($request);
    }
}
