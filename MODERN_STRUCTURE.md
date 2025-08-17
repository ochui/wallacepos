# WallacePOS Modern Project Structure

This document explains the modernized project structure that improves organization, maintainability, and security.

## New Directory Structure

```
wallacepos/
├── public/                    # Main public directory (web root)
│   ├── assets/               # Consolidated assets
│   │   ├── css/             # All stylesheets
│   │   ├── js/              # All JavaScript files
│   │   ├── images/          # All images
│   │   ├── fonts/           # All font files
│   │   ├── libs/            # Third-party libraries
│   │   └── sounds/          # Audio files
│   ├── admin/               # Admin interface
│   │   ├── index.html       # Admin entry point
│   │   └── content/         # Admin content pages
│   ├── kitchen/             # Kitchen display
│   │   └── index.html       # Kitchen entry point
│   ├── customer/            # Customer portal
│   │   ├── index.html       # Customer entry point
│   │   ├── content/         # Customer content pages
│   │   └── checkout/        # Checkout functionality
│   ├── index.html           # Main POS interface
│   └── .htaccess           # Routing and security rules
├── api/                     # Server-side API
├── library/                 # PHP libraries and models
├── installer/               # Installation system
├── docs-template/           # Document templates
└── [config files]          # Root configuration files
```

## Key Improvements

### 1. **Centralized Assets**
All static assets (CSS, JavaScript, images, fonts) are now consolidated in `public/assets/`:
- Eliminates duplicate files
- Easier to manage and update
- Cleaner dependency management
- Better caching strategies

### 2. **Proper Public Directory**
All publicly accessible files are in the `public/` directory:
- Improved security (server-side code not in web root)
- Better separation of concerns
- Standard web development practice
- Easier to configure web servers

### 3. **Logical Organization**
Applications are organized by function:
- `public/` - Main POS interface
- `public/admin/` - Administrative interface  
- `public/kitchen/` - Kitchen display system
- `public/customer/` - Customer portal

### 4. **Improved Routing**
Clean URL structure with `.htaccess` rules:
- `/` or `/pos` → Main POS interface
- `/admin` → Admin interface
- `/kitchen` → Kitchen display
- `/customer` → Customer portal
- `/api/*` → API endpoints (proxied)

### 5. **Enhanced Security**
- Sensitive files blocked from web access
- Security headers configured
- API proxying prevents direct access

## Migration Notes

### Asset References
All HTML files have been updated to use relative paths:
- Main app: `assets/...`
- Admin: `../assets/...`
- Kitchen: `../assets/...`
- Customer: `../assets/...`

### Web Server Configuration
Configure your web server to serve from the `public/` directory:

**Apache:**
```apache
DocumentRoot /path/to/wallacepos/public
```

**Nginx:**
```nginx
root /path/to/wallacepos/public;
```

### API Access
API endpoints remain accessible through the routing system:
- Frontend: `api/endpoint`
- Backend: Proxied to `../api/endpoint`

## Benefits

1. **Easier Debugging**: Clear separation between frontend and backend code
2. **Better Maintainability**: Logical organization makes finding files intuitive
3. **Improved Security**: Server-side code not exposed through web root
4. **Modern Standards**: Follows current web development best practices
5. **Scalability**: Structure supports future growth and refactoring

## Backward Compatibility

The old structure is preserved in parallel, but the new `public/` directory should be used as the primary entry point for better organization and security.