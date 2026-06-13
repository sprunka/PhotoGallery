# Chunk 1: Foundation & Authentication Plan

## Objective
Establish the core application architecture, directory structure, database connectivity, and administrative authentication. This chunk provides the secure baseline upon which the rest of the application will be built.

## Scope
- Framework and dependency installation.
- Environment variable configuration.
- Directory and routing scaffolding.
- Database schema for users.
- Authentication system (login/logout, middleware).

## Tasks
1. **Initialize Project & Dependencies**
   - Configure `composer.json` with Slim 4, PHP-DI, PSR-7 implementations, `vlucas/phpdotenv`, and dev tools (PHPUnit 12.5.29, PHPStan 2.2.2 level 6, PHP-CS-Fixer 3.95.4, Rector 1.0+).
   - Create testing configuration files: `phpunit.xml`, `phpstan.neon`, `.php-cs-fixer.php`, `rector.php`.
   - Create the base directory structure (`/public`, `/src`, `/protected`, `/templates`, `/config`, `/tests/Unit`, `/tests/Feature`, `/tests/Fixtures`).
   - Create a `README.md` containing basic project and setup instructions.
   - Create a `LICENSE` file.
2. **Server & Routing Configuration**
   - Create root, `/public`, and `/protected` `.htaccess` files to route traffic to `index.php` and protect raw files.
   - Setup `public/index.php` with Slim App initialization and PHP-DI container.
3. **Database & Configuration**
   - Create an `.env.example` file in the root with placeholders for SQL info (server, user, password, db name).
   - Create a protected `.env` file (ensure it is listed in `.gitignore`) with actual connection details.
   - Configure the application to load the `.env` file during initialization.
   - Setup PDO connection in the DI container using credentials from the `.env` file.
   - Create the initial database schema for the `users` table.
   - Create a script or temporary route to generate the initial `superadmin` user.
4. **Authentication**
   - Implement `AuthMiddleware` to protect `/admin` routes.
   - Create login and logout routes and controllers.
   - Implement session management for authenticated users.
   - **Write comprehensive tests** for authentication logic (TDD approach): test successful login, failed login, session timeout, logout. Target 90%+ coverage for auth module.
5. **Templating Foundation**
   - Decide on and integrate a templating engine (e.g., plain PHP or Twig) into the DI container.
   - Create base layouts for the Admin interface.
6. **Code Quality & CI/CD**
   - Setup GitHub Actions workflow (`.github/workflows/php.yml`) to enforce all quality gates on push/PR to `deliverable` branch.
   - Configure Composer scripts for local development: `test`, `test:coverage`, `lint`, `format`, `analyse`, `rector:check`, `rector:fix`.

## Acceptance Criteria
- Navigating to `/` returns a basic placeholder page.
- Navigating to `/admin` redirects to `/admin/login`.
- Successful login redirects to an empty `/admin` dashboard.
- `/protected` directory is inaccessible via browser.
- Database credentials are successfully loaded from `.env` and `.env` is ignored by git.
- **All code passes PHPStan level 6 analysis.**
- **All code follows PSR-12 standards enforced by PHP-CS-Fixer.**
- **Authentication tests pass with 90%+ coverage.**
- **GitHub Actions CI/CD workflow is configured and passing.**
