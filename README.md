# PhotoGallery CMS

A self-hosted photography gallery and content management system, designed for a single professional photographer. 

## Requirements
* PHP >= 8.5
* MySQL / MariaDB
* Apache with mod_rewrite
* PHP Extensions: `ext-gd`, `ext-pdo`, `ext-exif`

## Setup Instructions
1. Clone this repository to your host.
2. Run `composer install --no-dev --optimize-autoloader` (or `composer install` for local development).
3. Copy `.env.example` to `.env` and fill in your database credentials.
4. Point your web server root to the `public/` directory (or use the root `.htaccess` if hosted in a subfolder).
5. Ensure the `public/photos/` and `protected/originals/` directories are writable by the web server.

## Documentation
See `docs/DESIGN.md` for architectural overview and details.
