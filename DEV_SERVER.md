# WallacePOS Development Server

A simple CLI tool to start a local development server for WallacePOS with URL rewrite support.

## Features

- Built-in PHP development server
- URL rewriting that mimics Apache .htaccess behavior
- Support for all WallacePOS routes:
  - Main POS application (`/` → `index.html`)
  - Admin interface (`/admin/` → `admin/index.html`)
  - Kitchen display (`/kitchen/` → `kitchen/index.html`)
  - Customer portal (`/customer/` → `customer/index.html`)
  - API endpoints (`/api/*` → `api/wpos.php?a=*`)
  - Customer API (`/customerapi/*` → `api/customerapi.php?a=*`)
  - Library files (`/library/*` → `../library/*`)
  - Installer (`/installer/*` → `../installer/*`)
- Static file serving with proper MIME types
- Security: Blocks access to sensitive files (`.json`, `.htaccess`)

## Requirements

- PHP 7.0 or higher with CLI support

## Usage

### Option 1: PHP Script (Cross-platform)
```bash
php devserver.php [port] [host]
```

### Option 2: Shell Script (Linux/macOS)
```bash
./devserver.sh [port] [host]
```

### Option 3: Batch File (Windows)
```cmd
devserver.bat [port] [host]
```

## Parameters

- **port** (optional): Port number to run the server on. Default: `8080`
- **host** (optional): Host address to bind to. Default: `localhost`

## Examples

```bash
# Start server on default port 8080
php devserver.php

# Start server on port 3000
php devserver.php 3000

# Start server on port 8080, accessible from all interfaces
php devserver.php 8080 0.0.0.0

# Using shell script (Linux/macOS)
./devserver.sh 3000

# Using batch file (Windows)
devserver.bat 3000
```

## Accessing the Application

Once the server is running, you can access:

- **Main POS**: http://localhost:8080/
- **Admin Panel**: http://localhost:8080/admin/
- **Kitchen Display**: http://localhost:8080/kitchen/
- **Customer Portal**: http://localhost:8080/customer/
- **API Endpoints**: http://localhost:8080/api/...
- **Customer API**: http://localhost:8080/customerapi/...

## How It Works

The development server consists of two main components:

1. **devserver.php**: Main entry point that starts the PHP built-in server
2. **router.php**: URL router that handles rewriting rules and serves files

The router script processes incoming requests and:
- Serves static files directly
- Applies URL rewriting rules based on the original .htaccess configuration
- Routes API requests to the appropriate PHP files
- Blocks access to sensitive files

## Stopping the Server

Press `Ctrl+C` in the terminal to stop the server.

## Troubleshooting

### "PHP is not installed or not in PATH"
Make sure PHP is installed and available in your system's PATH. You can test this by running `php --version` in your terminal.

### "Document root directory does not exist"
Ensure the `public` directory exists in the same folder as the devserver.php script.

### Port already in use
If you get an error that the port is already in use, try a different port number:
```bash
php devserver.php 8081
```

### Permission denied (Linux/macOS)
Make sure the shell script is executable:
```bash
chmod +x devserver.sh
```
