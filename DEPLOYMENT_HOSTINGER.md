# Hostinger Deployment Guide

This backend is configured to work on both **Localhost** (`c:\xampp\htdocs\backend`) and **Hostinger** (`public_html/backend/`) without code changes, provided the configuration is correct.

## 1. Upload Files

Upload the entire contents of the `backend` folder to your Hostinger server.
Target Path: `public_html/backend/`

## 2. Database Configuration

The application reads database credentials from `.env`.
**IMPORTANT**: Do NOT upload your local `.env` file to Hostinger if it has `localhost` credentials (unless Hostinger DB is also on localhost with no password, which is unlikely).

### Option A: Create `.env` on Hostinger (Recommended)

Create a file named `.env` in `public_html/backend/` with your Hostinger DB details:

```env
DB_HOST=mysql.hostinger.com  <-- Replace with your specific Hostinger DB Host
DB_NAME=u123456789_migate_db
DB_USER=u123456789_migate_user
DB_PASS=YourStrongPassword
JWT_SECRET=YourSecretHere
```

### Option B: Edit `app/config/database.php`

If `.env` is not working, you can manually edit `app/config/database.php` on the server, but Option A is cleaner.

## 3. Folders & Permissions

Ensure the `uploads` folder exists and is writable.

- Path: `public_html/backend/uploads/`
- Permissions: 755 or 777 (if needed)

## 4. Testing

- **Localhost**: `http://localhost/backend/api/health`
- **Hostinger**: `http://yourdomain.com/backend/api/health`

Both should return:

```json
{
    "status": true,
    "message": "API is healthy",
    "data": { ... }
}
```

## Troubleshooting

- **404 Not Found**: Make sure `.htaccess` is present in the root (`backend/`).
- **500 Internal Server Error**: Check `public_html/error_log` for details. Common issues are DB connection failures.
- **Images not loading**: The system automatically detects if it's in the `/backend/` folder and adjusts image URLs accordingly.
