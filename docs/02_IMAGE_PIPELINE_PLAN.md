# Chunk 2: Image Pipeline & Photo Management Plan

## Objective
Implement the core photography features: uploading original high-resolution JPEGs, extracting metadata, generating watermarked display derivatives, and managing individual photos in the admin interface.

## Scope
- Image processing dependencies and setup.
- Database schema for photos.
- Upload workflow and `ImageService`.
- Photo CRUD operations in Admin interface.
- Secure streaming of original files.

## Tasks
1. **Pipeline Setup & Database**
   - Ensure `intervention/image` (v4.x) is installed and wired correctly in the DI container to use the GD2 driver.
   - Create the `photos` database table.
2. **Image Service Implementation**
   - Build `ImageService` to handle file uploads.
   - Implement SHA-256 hashing for filenames.
   - Implement EXIF extraction and normalization (handling different `source_device_type` logic and stripping GPS unconditionally).
   - Implement resizing logic (max 2048px on longest edge).
   - Implement watermark compositing.
   - **Write comprehensive tests using TDD approach**: Tests for hashing, EXIF parsing (each device type), GPS stripping, resizing edge cases, watermark placement. Target **90%+ coverage** for this high-risk module.
3. **Admin Upload & Photo Management**
   - Create `/admin/photos/upload` UI (form) and handler.
   - Create `/admin/photos` list view showing thumbnails.
   - Create `/admin/photos/{id}/edit` UI to manage title, caption, source camera, etc.
   - Implement photo deletion logic (removing DB record, original file, and display file).
   - **Write feature tests** for upload workflow, metadata persistence, and file cleanup on deletion.
4. **Secure File Access**
   - Implement the `/admin/originals/{hash}` route to securely stream files from `/protected/originals/` to authenticated admins only.
   - **Write tests** to verify authenticated access, 403 on unauthorized access, and proper file streaming.

## Acceptance Criteria
- Admin can upload a JPEG; original is saved in `/protected/originals/` and a watermarked derivative in `/public/photos/`.
- EXIF data is correctly parsed, UTF-8 encoded, and GPS data is stripped.
- Display images are resized correctly (max 2048px).
- Admin can view, edit metadata, and delete uploaded photos.
- Direct web access to `/protected/originals/...` returns 403 Forbidden.
- **ImageService tests pass with 90%+ coverage.**
- **All EXIF handling logic is verified for each device type.**
- **Code passes PHPStan level 6 analysis.**
- **All code follows PSR-12 standards.**
