<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

class AuthController
{
    private PDO $pdo;
    private Twig $twig;

    public function __construct(PDO $pdo, Twig $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    public function showLogin(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('admin.dashboard'))->withStatus(302);
        }

        return $this->twig->render($response, 'admin/login.twig');
    }

    public function processLogin(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $this->pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            // Success
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $user['id'];

            // Update last login
            $updateStmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('admin.dashboard'))->withStatus(302);
        }

        // Failure
        return $this->twig->render($response, 'admin/login.twig', [
            'error' => 'Invalid username or password.',
            'username' => $username
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        // Destroy session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('admin.login'))->withStatus(302);
    }
}
