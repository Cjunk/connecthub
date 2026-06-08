# Uploads Directory

This directory stores user-uploaded files such as:
- Profile images
- Group images  
- Event images
- Documents and attachments

## Security Notes

- Files in this directory should not be executable
- File types are restricted by the application
- All uploads are validated for size and type
- Filenames are generated using unique IDs to prevent conflicts

## Directory Structure

```
uploads/
├── profiles/     # User profile images
├── groups/       # Group images
├── events/       # Event images
└── documents/    # Other documents
```

## File Permissions

Ensure this directory has proper permissions:
- Directory: 755 (rwxr-xr-x)
- Files: 644 (rw-r--r--)

## Web Server Configuration

For Apache, ensure .htaccess prevents execution:
```apache
<Files "*">
    ForceType application/octet-stream
    Header set Content-Disposition attachment
</Files>
```

For Nginx:
```nginx
location ~* ^/uploads/.*\.(php|phtml|pl|py|jsp|asp|sh|cgi)$ {
    deny all;
}
```