#!/bin/bash

# WallacePOS Development Server Launcher
# Usage: ./devserver.sh [port] [host]

# Default values
PORT=${1:-8080}
HOST=${2:-localhost}

# Get the directory where this script is located
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Check if devserver.php exists
if [ ! -f "$DIR/devserver.php" ]; then
    echo "Error: devserver.php not found in $DIR"
    exit 1
fi

# Start the development server
echo "Starting WallacePOS Development Server..."
php "$DIR/devserver.php" "$PORT" "$HOST"
