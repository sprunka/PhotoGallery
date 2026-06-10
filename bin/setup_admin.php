<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use DI\ContainerBuilder;

// 1. Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 2. Build PHP-DI Container to get PDO
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

/** @var PDO $pdo */
$pdo = $container->get(PDO::class);

echo "PhotoGallery Setup: Create Superadmin\n";
echo "-------------------------------------\n";

// Prompt for username
echo "Username: ";
$username = trim(fgets(STDIN));

// Prompt for email
echo "Email: ";
$email = trim(fgets(STDIN));

// Prompt for password (no masking on standard windows CLI unfortunately, but simple enough for setup)
echo "Password: ";
$password = trim(fgets(STDIN));

if (empty($username) || empty($email) || empty($password)) {
    echo "Error: All fields are required.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'superadmin', 1)");
    $stmt->execute([$username, $email, $hash]);
    
    echo "\nSuccess: Superadmin user '{$username}' created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "\nError: User or email already exists.\n";
    } else {
        echo "\nDatabase Error: " . $e->getMessage() . "\n";
    }
    exit(1);
}
