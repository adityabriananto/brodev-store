#!/bin/sh
set -e

echo "Starting container entrypoint..."

# Ensure storage and bootstrap/cache are writable
echo "Configuring permissions..."
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/payment_proofs
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Recreate storage symlink if missing
if [ ! -L "/var/www/public/storage" ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# SQLite Persistent Database Handling
# If DB_CONNECTION is sqlite, ensure the file exists and has correct ownership
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH=${DB_DATABASE:-/data/database.sqlite}
    echo "SQLite database detected. Checking path: $DB_PATH"
    
    # Ensure directory exists
    DB_DIR=$(dirname "$DB_PATH")
    if [ ! -d "$DB_DIR" ]; then
        echo "Creating database directory: $DB_DIR"
        mkdir -p "$DB_DIR"
        chown -R www-data:www-data "$DB_DIR"
        chmod 775 "$DB_DIR"
    fi

    # Ensure database file exists
    if [ ! -f "$DB_PATH" ]; then
        echo "Database file not found. Creating $DB_PATH..."
        touch "$DB_PATH"
        chown www-data:www-data "$DB_PATH"
        chmod 664 "$DB_PATH"
    fi
    
    # Run migrations during startup (since SQLite volume isn't mounted during fly deploy release_command)
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
    chown www-data:www-data "$DB_PATH"
fi

# If custom command arguments are passed, execute them instead of supervisor
if [ $# -gt 0 ]; then
    echo "Custom command detected. Executing: $@"
    exec "$@"
fi

# Run caching optimizations for production
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
echo "Launching supervisord..."
exec supervisord -c /etc/supervisor/supervisord.conf
