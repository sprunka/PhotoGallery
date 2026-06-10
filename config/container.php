<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    PDO::class => function (ContainerInterface $c) {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'photogallery';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Ensure prepared statements are not emulated for security
            PDO::ATTR_EMULATE_PREPARES => false, 
        ];

        return new PDO($dsn, $user, $pass, $options);
    },

    Twig::class => function (ContainerInterface $c) {
        // Initialize Twig with the templates directory
        $twig = Twig::create(__DIR__ . '/../templates', [
            'cache' => false, // Disable cache for development. Configurable via ENV later.
        ]);
        
        $twig->getEnvironment()->addGlobal('session', $_SESSION);
        
        return $twig;
    },
];
