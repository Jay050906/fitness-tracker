#!/bin/sh
set -e

# Default PORT to 80 if not set in environment (Render sets PORT dynamically)
PORT="${PORT:-80}"

# Update Apache port configuration dynamically for Render environment variable
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/*.conf

exec "$@"
