# Photography Gallery CMS — Design & Architecture Document

> **LIVING DOCUMENT** — Subject to revision at any time.  
> Requirements, design decisions, and implementation details are expected to evolve.

| | |
|---|---|
| **Document Status** | Draft v0.8.1 |
| **Created** | 2026-06-09 |
| **Last Revised** | 2026-06-09 — v0.8.1 |
| **Author** | Sean Prunka |
| **Project** | Self-Hosted Photography Gallery |

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technical Stack](#2-technical-stack)
3. [Directory & File Structure](#3-directory--file-structure)
4. [Image Pipeline](#4-image-pipeline)
5. [Database Design](#5-database-design)
6. [Routing](#6-routing)
7. [Security](#7-security)
8. [Admin Interface](#8-admin-interface)
9. [Public Gallery — UX & Display](#9-public-gallery--ux--display)
10. [Future Considerations](#10-future-considerations)
11. [Open Questions & Decisions Pending](#11-open-questions--decisions-pending)
12. [Revision History](#12-revision-history)

---

## 1. Project Overview

This document describes the design and architecture of a self-hosted photography gallery and content management system. It is a living document — requirements, design decisions, and implementation details are expected to evolve as the project develops.

The system is built for a single professional photographer who is also the sole developer. The guiding principle throughout is that the platform should serve the photography, not the other way around. Every architectural and aesthetic decision flows from that principle.

### 1.1 Goals

- Provide a clean, professional, dark-themed public gallery for displaying photographic work
- Organize photographs into named albums/galleries browsable by visitors
- Protect original full-resolution files from any form of public access
- Serve watermarked display-resolution images to the public
- Provide a private, authenticated admin interface for uploading and managing images
- Store original files as tertiary backup on the web host's unlimited storage
- Remain entirely self-hosted on existing shared hosting infrastructure

### 1.2 Non-Goals (Current Scope)

- Print sales fulfillment — deferred to a future phase
- Public user registration or community features
- RAW file handling — JPEG originals only
- Video hosting
- E-commerce of any kind in the initial release

> **NOTE:** Print sales integration (Whitewall via Shopify) is a planned future phase. The architecture must not preclude it, but it will not be built in v1.

---

## 2. Technical Stack

> **NOTE:** All version numbers reflect minimums at time of writing. These may be revised upward as the host or project warrants.

### 2.1 Server Environment

| | |
|---|---|
| **Hosting Type** | Shared hosting — existing provider, no migration planned |
| **PHP Version** | 8.5.4 (host current) — minimum PHP 8.5 |
| **Database** | MySQL — version as provided by host (credentials loaded via protected `.env`) |
| **Web Server** | Apache with mod_rewrite (confirmed available) |
| **File Storage** | Unlimited (host claim) — currently ~1.32 GB used |

### 2.2 Application Framework

| | |
|---|---|
| **Framework** | Slim Framework 4 |
| **Minimum Version** | 4.15.2 (current latest at project start) |
| **Routing Pattern** | Front controller via `.htaccess` — all requests route through `/public/index.php` |
| **Architecture Style** | MVC-adjacent — thin routes, service classes, no heavy ORM |

### 2.3 Key Dependencies

> **NOTE:** Dependency versions are pinned in `composer.json` and updated deliberately. Versions below reflect current pins.

**Runtime:**

| Package | Version | Purpose |
|---|---|---|
| `slim/slim` | 4.15.2 | Core framework |
| `vlucas/phpdotenv` | 5.6.1 | Loads environment variables from `.env` |
| `slim/psr7` | 1.8.0 | PSR-7 HTTP message implementation |
| `php-di/slim-bridge` | 3.4.1 | PHP-DI integration for Slim — handles container wiring and route handler injection |
| `php-di/php-di` | 7.1.1 | Dependency injection container |
| `friendsofphp/proxy-manager-lts` | v1.0.19 | Lazy injection proxy support for PHP-DI |
| `intervention/image` | 4.1.3 | Fluent image processing API over GD2 — resizing, watermark compositing, format conversion. **Must be explicitly instantiated with GD2 driver** — see DI container note below |
| `ext-gd` | * | GD2 driver for Intervention Image — host confirmed available, Imagick not available |
| `ext-pdo` | * | Database access — prepared statements exclusively |
| `ext-exif` | * | EXIF extraction from uploaded JPEGs — confirmed enabled; supports JPEG+TIFF, extended tags for major camera brands. **Note:** `exif.encode_unicode` is `ISO-8859-15` on host — EXIF strings must be passed through `mb_convert_encoding()` to UTF-8 before storage in `utf8mb4` MySQL columns |

**Development:**

| Package | Version | Purpose |
|---|---|---|
| `phpunit/phpunit` | 12.5.29 | Unit testing |
| `squizlabs/php_codesniffer` | 4.0.1 | Code style linting |
| `friendsofphp/php-cs-fixer` | 3.95.4 | Automated code style fixing |
| `phpstan/phpstan` | 2.2.2 | Static analysis — enforced at strict level |

> **NOTE:** `ext-gd`, `ext-pdo`, and `ext-exif` are PHP extensions declared in `composer.json` as `"ext-gd": "*"` etc. — they are not Composer-installable packages. Confirm all three are available on the host before beginning image pipeline work.

> **NOTE — Intervention Image DI wiring:** Version 4.x does not auto-detect the driver. `ImageManager` must be explicitly bound in the DI container config with the GD2 driver:
> ```php
> use Intervention\Image\ImageManager;
> use Intervention\Image\Drivers\Gd\Driver;
>
> // In container definitions:
> ImageManager::class => fn() => ImageManager::withDriver(new Driver()),
> ```
> `ImageService` then receives `ImageManager` via constructor injection. Do not instantiate it directly inside service methods.

### 2.4 Front-End

| | |
|---|---|
| **CSS Framework** | Bootstrap 5.3.8 |
| **Dark Mode** | Bootstrap native `data-bs-theme="dark"` |
| **Serving** | Self-hosted under `/public/assets/` — no CDN dependency |
| **JavaScript** | Bootstrap 5 bundle (no jQuery) + bespoke JS where needed |
| **Build Pipeline** | None — no Node, no Webpack, no Sass compilation required |

- Bootstrap used for layout, grid, and components — not for aesthetic personality
- Custom CSS layered on top for typography, color overrides, and gallery-specific styling
- The photographer's own aesthetic is expressed through customization, not through Bootstrap defaults
- Fully responsive — Bootstrap's grid handles breakpoints
- JavaScript used sparingly and only where it genuinely improves UX

---

## 3. Directory & File Structure

### 3.1 Project Root Layout

The project follows the same front-controller pattern used across the developer's existing Slim applications on this host. Everything outside `/public` is unreachable via HTTP.

```
/gallery-root/
  /public/                  ← Web root (Apache DocumentRoot or subdomain root)
    index.php               ← Front controller
    .htaccess               ← Rewrite rules
    /assets/                ← CSS, JS, fonts (public)
    /photos/                ← Watermarked display images (public)
      /{album_slug}/
        {image_hash}.jpg
  /protected/               ← Never accessible via HTTP
    /originals/             ← Original uploaded JPEGs
      /{user_id}/
        {image_hash}.jpg
  /src/                     ← Application source
    /Controllers/
    /Services/
    /Models/
    /Middleware/
  /templates/               ← HTML templates (Twig or plain PHP — TBD)
  /config/                  ← Configuration files
  /vendor/                  ← Composer dependencies
  .env                      ← Protected environment variables (NOT committed)
  .env.example              ← Template for environment variables (committed)
  composer.json
  .htaccess                 ← Routes to /public
  DESIGN.md                 ← This document
```

### 3.2 `.htaccess` — Root Level

Identical pattern to developer's existing Slim applications:

```apache
RewriteEngine on
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

### 3.3 `.htaccess` — `/public` Level

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

### 3.4 `.htaccess` — `/protected` Level

Belt-and-suspenders protection. The directory is outside the web root by design, but this provides an explicit additional denial layer:

```apache
Options -Indexes
Deny from all
```

---

## 4. Image Pipeline

### 4.1 Upload Flow

1. Admin uploads JPEG via authenticated admin interface
2. Original JPEG is written to `/protected/originals/{user_id}/{image_hash}.jpg`
3. `ImageService` reads original and generates display derivative(s) via Intervention Image (GD2 driver)
4. Watermark is burned into all display derivatives via Intervention Image composite — **never the original**
5. Display derivative is written to `/public/photos/{album_slug}/{image_hash}.jpg`
6. Database record is created linking original path, display path, album, and metadata
7. EXIF data extracted via `exif_read_data()` — strings converted from ISO-8859-15 to UTF-8 via `mb_convert_encoding()` before storage in JSON column

> **NOTE — EXIF reliability by source type:** Pipeline behavior varies by `source_device_type`:
>
> | Source Type | EXIF Behavior |
> |---|---|
> | `digital_camera` | Full EXIF auto-populated. Canon 1000D, T6i, R50, R6 III all report cleanly. `taken_at` and `source_camera` default to EXIF values. |
> | `film_scan` | EXIF reflects scanner, not original capture. `taken_at`, camera model, and lens data suppressed — left for manual entry. `source_camera` free-text for original body (e.g. "Canon AE-1P"). |
> | `mobile` | EXIF present but inconsistent. Make/model stored as-is. Focal length equivalents unreliable. `taken_at` generally trustworthy. |
> | `unknown` | EXIF absent or unreadable. All metadata fields left for manual entry. Pipeline must not crash on missing EXIF — store null and continue. |
>
> **NOTE — GPS stripping:** GPS coordinates are stripped from `exif_data` before database storage in all cases, regardless of source type. This is unconditional — not a configuration option. If GPS data is ever to be displayed it must be entered manually and deliberately. Display derivatives generated by the pipeline never carry GPS data.
>
> **NOTE — Mobile HEIC:** Pipeline accepts JPEG only. HEIC/HEIF files from iOS devices must be converted to JPEG before upload. This is the photographer's responsibility — no server-side HEIC conversion will be implemented.

> **NOTE:** `image_hash` is generated from file contents (SHA-256), not from filename or timestamp. This ensures deduplication and makes paths non-guessable.

### 4.2 Display Image Specifications

> **NOTE:** These are starting defaults. They are configurable and subject to revision.

| | |
|---|---|
| **Maximum dimension** | 2048px on the longest edge (approximately 2K display quality) |
| **Aspect ratio** | Preserved exactly — no forced cropping, ever |
| **Format** | JPEG, quality 85 |
| **Color profile** | sRGB (converted from any input profile) |
| **Watermark** | Burned in at generation time — position, opacity, and content TBD |

### 4.3 Original File Access

- Originals are never linked, referenced, or mentioned in any public-facing HTML
- Originals are accessible only via an authenticated Slim route
- The route verifies session authentication before streaming the file
- The file is streamed via PHP `readfile()` — the real filesystem path is **never** exposed to the client
- Access is logged

> **NOTE:** In v1 the only authorized user is the global admin. Future versions may extend this to per-user authorization for their own uploads.

### 4.4 Watermark Specification

> **⚠ TO BE DEFINED** — Developer to specify watermark content, position, size, and opacity.

- Watermark asset stored outside the web root
- Applied at generation time via Intervention Image composite operation
- Position: corner (configurable), tiled, or centered — TBD
- Opacity: TBD — sufficient to deter casual use, not so heavy as to obscure the image

---

## 5. Database Design

> **NOTE:** Schema is a starting point. Column types and indexes will be refined during implementation.
>
> **NOTE:** Database connection details (server, username, password, database name) must be read from a protected `.env` file. An `.env.example` must be provided in the repository.

### 5.1 Tables Overview

| Table | Purpose |
|---|---|
| `users` | Admin accounts |
| `albums` | Gallery groupings |
| `photos` | Individual images |
| `photo_album` | Junction table — photo can belong to multiple albums |
| `sessions` | Server-side session storage (optional — may use PHP native sessions) |

### 5.2 `users`

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | Auto-increment |
| `username` | `VARCHAR(100)` | Unique, login handle |
| `email` | `VARCHAR(255)` | Unique |
| `password_hash` | `VARCHAR(255)` | bcrypt via `password_hash()` |
| `role` | `ENUM('superadmin','admin')` | superadmin = global owner |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP |
| `last_login_at` | `TIMESTAMP` | Nullable |
| `is_active` | `TINYINT(1)` | Soft disable without deletion |

### 5.3 `albums`

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | Auto-increment |
| `slug` | `VARCHAR(200)` | Unique, URL-safe identifier |
| `title` | `VARCHAR(255)` | Display name |
| `description` | `TEXT` | Nullable, optional |
| `cover_photo_id` | `BIGINT UNSIGNED` FK | Nullable, references `photos.id` |
| `parent_album_id` | `BIGINT UNSIGNED` FK | Nullable, self-ref for nesting |
| `sort_order` | `INT` | Manual ordering within parent |
| `is_published` | `TINYINT(1)` | 0 = draft/hidden from public |
| `created_by` | `BIGINT UNSIGNED` FK | References `users.id` |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | `TIMESTAMP` | ON UPDATE CURRENT_TIMESTAMP |

### 5.4 `photos`

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | Auto-increment |
| `hash` | `CHAR(64)` | SHA-256 of file contents — unique, used in filenames |
| `original_filename` | `VARCHAR(255)` | Original upload filename, stored for reference |
| `original_path` | `VARCHAR(500)` | Relative path under `/protected/originals/` |
| `display_path` | `VARCHAR(500)` | Relative path under `/public/photos/` |
| `title` | `VARCHAR(255)` | Nullable, display title |
| `caption` | `TEXT` | Nullable |
| `taken_at` | `TIMESTAMP` | From EXIF if available, nullable |
| `uploaded_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP |
| `uploaded_by` | `BIGINT UNSIGNED` FK | References `users.id` |
| `width_original` | `INT UNSIGNED` | Original image width in px |
| `height_original` | `INT UNSIGNED` | Original image height in px |
| `width_display` | `INT UNSIGNED` | Display derivative width in px |
| `height_display` | `INT UNSIGNED` | Display derivative height in px |
| `file_size_original` | `BIGINT UNSIGNED` | Bytes |
| `exif_data` | `JSON` | Nullable, selected EXIF fields — GPS stripped before storage (see pipeline note) |
| `source_device_type` | `ENUM('digital_camera','film_scan','mobile','unknown')` | Source type — drives EXIF handling behavior in pipeline and display logic |
| `source_camera` | `VARCHAR(255)` | Nullable, manually overridable — defaults to EXIF camera model; meaningless for film scans and inconsistent for mobile |
| `is_published` | `TINYINT(1)` | 0 = not visible publicly |
| `salable` | `ENUM('none','snapshot','full')` | Print sale eligibility — **not implemented in v1, no functional effect yet.** `none` = never for sale; `snapshot` = small prints only (up to approx. 8×10 or equivalent); `full` = full art prints including poster sizes. Exists to classify images now so the data is clean when print sales are implemented. Default `none`. |

### 5.5 `photo_album`

| Column | Type | Notes |
|---|---|---|
| `photo_id` | `BIGINT UNSIGNED` FK | References `photos.id` |
| `album_id` | `BIGINT UNSIGNED` FK | References `albums.id` |
| `sort_order` | `INT` | Photo's position within this specific album |

> Composite primary key on `(photo_id, album_id)`.

---

## 6. Routing

### 6.1 Public Routes

| Route | Method | Description |
|---|---|---|
| `/` | GET | Home / landing page — featured or recent albums |
| `/gallery` | GET | All albums index |
| `/gallery/{slug}` | GET | Single album — all photos in that album |
| `/photo/{hash}` | GET | Single photo detail view |
| `/about` | GET | About page (optional, TBD) |

### 6.2 Admin Routes

> All admin routes are protected by `AuthMiddleware`. Unauthenticated requests redirect to `/admin/login`.

| Route | Method | Description |
|---|---|---|
| `/admin/login` | GET | Login form |
| `/admin/login` | POST | Authenticate, set session |
| `/admin/logout` | POST | Destroy session |
| `/admin` | GET | Admin dashboard |
| `/admin/photos` | GET | Photo management list |
| `/admin/photos/upload` | GET | Upload form |
| `/admin/photos/upload` | POST | Handle upload, process derivatives |
| `/admin/photos/{id}/edit` | GET | Edit photo metadata |
| `/admin/photos/{id}/edit` | POST | Save photo metadata |
| `/admin/photos/{id}` | DELETE | Delete photo and all derivatives |
| `/admin/albums` | GET | Album management list |
| `/admin/albums/create` | GET | New album form |
| `/admin/albums/create` | POST | Save new album |
| `/admin/albums/{id}/edit` | GET | Edit album |
| `/admin/albums/{id}/edit` | POST | Save album changes |
| `/admin/albums/{id}` | DELETE | Delete album |
| `/admin/originals/{hash}` | GET | Stream original file — authenticated only |

---

## 7. Security

### 7.1 File Protection

- Original files live in `/protected/` which is outside the document root by directory structure
- `/protected/.htaccess` adds explicit `Deny from all` as belt-and-suspenders
- Display image filenames are SHA-256 content hashes — non-guessable, non-sequential
- No HTML anywhere in the application ever references an original file path
- Original files are served only via PHP stream through an authenticated route

### 7.2 Authentication

- Passwords stored as bcrypt hashes via `password_hash(PASSWORD_BCRYPT)`
- Sessions use PHP native session handling with regenerated IDs on login
- Session cookie: `HttpOnly`, `SameSite=Strict`, `Secure` (if HTTPS available)
- No remember-me / persistent login in v1
- Failed login attempts are rate-limited — TBD implementation

### 7.3 Input Handling

- All database queries use PDO prepared statements — no string interpolation, ever
- All output is escaped via `htmlspecialchars()` or template engine auto-escaping
- File uploads validated: MIME type (server-side, not client `Content-Type`), extension, file size limit
- CSRF protection on all state-changing POST/DELETE routes
- Upload directory is never the web root

### 7.4 HTTP Security Headers

> To be implemented via Slim middleware on all responses.

- `Content-Security-Policy` — restrictive, images from self only
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`

---

## 8. Admin Interface

### 8.1 Principles

- Functional over decorative — the admin UI is a tool, not a showcase
- Dark theme consistent with the public gallery
- Single-user optimized — no multi-user workflow complexity in v1
- Fast to use — uploading and organizing photos should be efficient, not ceremonial

### 8.2 Upload Workflow

- Single or multi-file upload supported
- EXIF data extracted automatically on upload (date taken, camera, lens, exposure data)
- Title and caption editable post-upload — not required at upload time
- Album assignment at upload time or via edit — photos can belong to multiple albums
- Upload progress indicator for large files

### 8.3 Photo Management

- List view with thumbnail, title, album(s), upload date, published status
- Inline publish/unpublish toggle
- Edit: title, caption, album assignments, sort order within album
- View original: authenticated stream, opens in new tab — UX TBD
- Delete: removes display derivative, removes original, removes DB record — with confirmation

### 8.4 Album Management

- Create, edit, delete albums
- Set cover photo per album
- Reorder photos within album via `sort_order`
- Nested albums supported by data model — admin UI for nesting is TBD
- Publish/unpublish entire album

---

## 9. Public Gallery — UX & Display

### 9.1 Design Principles

- The photographs are the UI — chrome is minimal and secondary
- Dark background throughout — the photographs define the visual experience
- No clutter: no like buttons, no share buttons, no social proof widgets
- Navigation is present but unobtrusive
- Typography is clean, restrained, and serves the images

### 9.2 Album Index Page

- Grid of album cover images
- Album title on hover or below image — TBD
- Click navigates to album view
- Pagination TBD — threshold depends on total album count

### 9.3 Album View Page

- Grid of watermarked display images
- Click on image opens single photo view
- Album title and optional description displayed
- No right-click suppression — this is security theater and will not be implemented
- No size picker, no download link, no original reference of any kind in the HTML

### 9.4 Single Photo View

- Large display image centered on dark background
- Title and caption if present
- Selected EXIF data if present (camera, lens, exposure — TBD which fields)
- Previous / next navigation within album
- No social sharing, no download, no external links in v1

### 9.5 Comments

> **⚠ DEFERRED** — Nice to have, not in v1.

- If implemented: moderated, admin-approved before display
- No third-party comment system (Disqus etc.) — self-hosted or not at all
- Simple name + comment form, no accounts required for commenters

---

## 10. Future Considerations

> These items are out of scope for v1 but the architecture must not preclude them.

### 10.1 Print Sales

- Whitewall via Shopify Starter ($5/month) is the preferred path when pursued
- Integration model: Shopify Buy Button embedded per photo, links to product page
- Original files for print fulfillment are uploaded directly to Whitewall/Shopify — completely separate from the gallery
- Gallery originals and print originals are independent — no API connection needed
- Pricing model: Whitewall base cost + photographer markup, set per image
- `salable` field already present in schema — classification can begin immediately, enforcement added when print sales are built
- `salable = 'snapshot'` implies a maximum print dimension cap to be defined when the print sales integration is designed

### 10.2 EXIF Display

Fields under consideration for public display:
- Camera make and model
- Lens
- Focal length, aperture, shutter speed, ISO
- Date taken
- Location — only if photographer explicitly enables per image

### 10.3 Additional Admin Users

- Role-based access: `superadmin` can see all originals; `admin` can see own uploads only
- Per-user upload folders already accounted for in schema: `/protected/originals/{user_id}/`

### 10.4 Archive Access UI

- Authenticated interface for browsing and downloading own originals
- May be a separate route group from the gallery admin

### 10.5 Lightroom Integration

> **SPECULATIVE** — Low priority.

- Potential Lightroom publish plugin or watched-folder approach for semi-automated upload

---

## 11. Open Questions & Decisions Pending

Items to resolve and move to the relevant section when decided:

- [ ] **Templating engine** — Plain PHP templates vs Twig. Slim works with either.
- [ ] **Watermark** — Exact content, position, opacity, and size
- [ ] **Display image max dimension** — 2048px is the starting default; revisit based on visual quality testing
- [ ] **EXIF fields** — Which fields to display publicly on single photo view
- [ ] **Nested album UI** — Data model supports it; admin UI approach TBD
- [ ] **Login rate limiting** — Implementation approach (DB-based vs APCu vs file-based)
- [ ] **HTTPS** — Availability and enforcement on the shared host
- [ ] **Album cover selection** — Auto (first photo) vs manual selection
- [ ] **Photo sort order within album** — Upload order, date taken, or manual drag-to-reorder
- [ ] **About page** — Include or omit; content TBD
- [ ] **Home page** — Featured album, most recent album, or curated selection TBD

---

## 12. Revision History

| Version | Date | Author | Changes |
|---|---|---|---|
| 0.1 | 2026-06-09 | Sean Prunka | Initial draft — architecture, stack, DB schema, routing, security, image pipeline |
| 0.2 | 2026-06-09 | Sean Prunka | Frontend: Bootstrap 5.3.8; image processing: GD2; dependencies: pinned versions, added slim-bridge, dev dependencies |
| 0.3 | 2026-06-09 | Sean Prunka | Dependencies: added intervention/image 4.1.3 (GD2 driver) replacing raw GD2 calls; added phpstan/phpstan 2.2.2 to dev |
| 0.4 | 2026-06-09 | Sean Prunka | Added DI container wiring note for Intervention Image 4.x explicit GD2 driver instantiation |
| 0.5 | 2026-06-09 | Sean Prunka | Confirmed ext-exif available on host; added ISO-8859-15 → UTF-8 encoding requirement for EXIF strings before MySQL storage |
| 0.6 | 2026-06-09 | Sean Prunka | Camera confirmed Canon R6 III; added film scan handling — is_film_scan flag, source_camera override field, EXIF unreliability note for scanned negatives (AE-1P, A1) |
| 0.7 | 2026-06-09 | Sean Prunka | Refactored is_film_scan boolean to source_device_type ENUM (digital_camera, film_scan, mobile, unknown); added GPS unconditional strip from EXIF; added per-source-type pipeline behavior table; added HEIC rejection note; accounted for 1000D, T6i, R50, mobile devices as valid sources |
| 0.8 | 2026-06-09 | Sean Prunka | Added salable ENUM field (none/snapshot/full) to photos table — placeholder only, no v1 functionality; documented in future print sales section |
| 0.8.1 | 2026-06-09 | Sean Prunka | Added requirement for protected `.env` file for database credentials and `.env.example` template |
