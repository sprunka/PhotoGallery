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
3. **Admin Upload & Photo Management**
   - Create `/admin/photos/upload` UI (form) and handler.
   - Create `/admin/photos` list view showing thumbnails.
   - Create `/admin/photos/{id}/edit` UI to manage title, caption, source camera, etc.
   - Implement photo deletion logic (removing DB record, original file, and display file).
4. **Secure File Access**
   - Implement the `/admin/originals/{hash}` route to securely stream files from `/protected/originals/` to authenticated admins only.

## Acceptance Criteria
- Admin can upload a JPEG; original is saved in `/protected/originals/` and a watermarked derivative in `/public/photos/`.
- EXIF data is correctly parsed, UTF-8 encoded, and GPS data is stripped.
- Display images are resized correctly (max 2048px).
- Admin can view, edit metadata, and delete uploaded photos.
- Direct web access to `/protected/originals/...` returns 403 Forbidden.
