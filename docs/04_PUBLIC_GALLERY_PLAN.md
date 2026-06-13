# Chunk 4: Public Gallery & UI Plan

## Objective
Deliver the final public-facing gallery using the defined dark-themed aesthetic, ensuring visitors can browse albums and view watermarked photos seamlessly and responsively.

## Scope
- Public frontend routes and controllers.
- Bootstrap 5 integration and custom CSS.
- UX implementation for albums and individual photos.
- Security headers.

## Tasks
1. **Frontend Foundation**
   - Include Bootstrap 5.3.8 (local under `/public/assets/`).
   - Setup base layout template with `data-bs-theme="dark"`.
   - Add custom CSS for typography and gallery-specific styling.
2. **Public Routes & Views**
   - Implement `/` (Home) route showing featured/recent albums.
   - Implement `/gallery` route showing an index of all published albums.
   - Implement `/gallery/{slug}` route showing a grid of photos in that album.
   - Implement `/photo/{hash}` route for the single-photo detail view.
   - **Write feature tests** for route accessibility, published/unpublished filtering, and proper data rendering.
3. **Photo Display & Metadata**
   - Ensure the single photo view displays the watermarked derivative only.
   - Display EXIF data (if appropriate based on `source_device_type` and privacy rules).
   - Implement prev/next navigation within an album context.
   - **Write tests** to verify watermarked images are served, sensitive paths are never exposed, and EXIF display logic respects privacy rules.
4. **Security & Final Polish**
   - Implement global middleware for HTTP security headers (CSP, X-Frame-Options, etc.).
   - Review performance, accessibility, and mobile responsiveness.
   - **Run full CI/CD pipeline**: PHPUnit (80%+ coverage minimum), PHPStan level 6, PHP-CS-Fixer, Rector checks.

## Acceptance Criteria
- Unauthenticated visitors can browse published albums and view photos.
- The UI is entirely dark-themed, using Bootstrap 5 and custom CSS.
- Original files are never linked or exposed in the HTML source.
- Security headers are present on all public responses.
- **All public routes have 80%+ test coverage.**
- **Security tests verify watermarked images only, no sensitive paths exposed.**
- **Code passes PHPStan level 6 analysis.**
- **All code follows PSR-12 standards.**
- **Full CI/CD pipeline passes all quality gates: PHPUnit, PHPStan, PHP-CS-Fixer, Rector.**
