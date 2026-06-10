# Chunk 3: Albums & Organization Plan

## Objective
Provide the structural grouping for the gallery by allowing the admin to create albums, assign photos to them, and manage sorting and presentation details.

## Scope
- Database schema for albums and album-photo relationships.
- Admin UI for album management.
- Integration of albums into the photo upload/edit workflow.

## Tasks
1. **Database Schema**
   - Create the `albums` table.
   - Create the `photo_album` junction table.
2. **Admin Album Management**
   - Create `/admin/albums` list view.
   - Create `/admin/albums/create` form and handler.
   - Create `/admin/albums/{id}/edit` form and handler.
   - Implement ability to set a `cover_photo_id`.
   - Implement album deletion logic (handling relationships appropriately).
3. **Photo-Album Integration**
   - Update photo upload and edit forms to allow selecting one or more albums.
   - Implement logic to handle the `photo_album` junction entries.
4. **Sorting & Organization**
   - Implement functionality to define `sort_order` for photos within an album.
   - (Optional) Implement UI for simple reordering within the album edit view.

## Acceptance Criteria
- Admin can create, edit, and delete albums.
- Admin can assign a photo to one or multiple albums.
- Admin can define a cover photo for an album.
- Photos within an album can be manually ordered.
