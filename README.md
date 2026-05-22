# Face Recognition Photo Manager

A web-based face-recognition photo management system for photography studios and customers built with PHP/MySQL (UI, auth, album management, QR code generation, email notifications) and a Python OpenCV component (`face_matcher.py`) that detects faces with Haar cascades and uses template matching to find similar images in studio albums; uploads are stored under `uploads/`, configuration is in `config.php`, and third-party libraries include `phpqrcode` and PHPMailer.

## File map
- [index.php](index.php) — unified login and entry point
- [config.php](config.php) — database and path configuration
- [functions.php](functions.php) — shared helper functions
- [face_matcher.py](face_matcher.py) — OpenCV face detection & matching script
- [studio/](studio/) — studio dashboard, album and customer management
- [customer/](customer/) — customer dashboard and album views
- [admin/](admin/) — administrative pages for studios and system settings
- [uploads/](uploads/) — `albums/`, `covers/`, `qrcodes/`, `search_temp/`
- [phpqrcode/](phpqrcode/) — QR code generator library
- [vendor/](vendor/) — Composer-managed dependencies (PHPMailer)

## Quick setup
1. Create a MySQL database named `face_recognition_app` (or update `config.php`).
2. Update DB credentials in `config.php`.
3. Install PHP dependencies:

```bash
composer install
```

4. Install Python dependency for face matching:

```bash
pip install opencv-python
```

5. Example: run the face matcher from command line:

```bash
python face_matcher.py uploads/search_temp/upload.jpg uploads/albums/18/
```

## Notes
- `face_matcher.py` uses OpenCV Haar cascades and template matching; consider improving matching (LBPH, FaceNet) for robustness.
- Ensure webserver user has write permissions to `uploads/` directories.
