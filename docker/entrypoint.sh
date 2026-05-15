#!/bin/bash
set -e

echo "=== TableTrack AWS Entrypoint ==="

# -----------------------------------------------------------------------
# 1. Write .env from environment variables (injected by ECS / EB / Docker)
# -----------------------------------------------------------------------
cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-TableTrack}"
APP_KEY="${APP_KEY}"
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

MAIN_APPLICATION_SUBDOMAIN="${MAIN_APPLICATION_SUBDOMAIN:-}"
REDIRECT_HTTPS="${REDIRECT_HTTPS:-false}"

# Database (AWS RDS MySQL)
DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"
DB_SSL_CA="${DB_SSL_CA:-}"

# Cache / Queue
CACHE_DRIVER="${CACHE_DRIVER:-file}"
SESSION_DRIVER="${SESSION_DRIVER:-file}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

# Mail
MAIL_MAILER="${MAIL_MAILER:-smtp}"
MAIL_HOST="${MAIL_HOST:-}"
MAIL_PORT="${MAIL_PORT:-587}"
MAIL_USERNAME="${MAIL_USERNAME:-}"
MAIL_PASSWORD="${MAIL_PASSWORD:-}"
MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-TableTrack}"

# AWS S3 (for file storage / CDN)
AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-}"
AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-}"
AWS_DEFAULT_REGION="${AWS_DEFAULT_REGION:-ap-south-1}"
AWS_BUCKET="${AWS_BUCKET:-}"
AWS_BUCKET_BUILD="${AWS_BUCKET_BUILD:-}"
AWS_BUCKET_BUILD_FOLDER="${AWS_BUCKET_BUILD_FOLDER:-}"
AWS_URL="${AWS_URL:-}"
CDN_ENABLED="${CDN_ENABLED:-false}"
CDN_URL="${CDN_URL:-}"
FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"

# Pusher / Broadcasting
BROADCAST_DRIVER="${BROADCAST_DRIVER:-log}"
PUSHER_APP_ID="${PUSHER_APP_ID:-}"
PUSHER_APP_KEY="${PUSHER_APP_KEY:-}"
PUSHER_APP_SECRET="${PUSHER_APP_SECRET:-}"
PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER:-}"

# Payment gateways (add as needed)
STRIPE_KEY="${STRIPE_KEY:-}"
STRIPE_SECRET="${STRIPE_SECRET:-}"
EOF

echo "[OK] .env written"

# -----------------------------------------------------------------------
# 2. Laravel bootstrap
# -----------------------------------------------------------------------
cd /var/www/html

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[OK] Laravel caches built"

# -----------------------------------------------------------------------
# 3. Run migrations (safe – only applies new migrations)
# -----------------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
    echo "[OK] Migrations complete"
fi

# -----------------------------------------------------------------------
# 4. Fix permissions (volumes may have overwritten them)
# -----------------------------------------------------------------------
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "[OK] Permissions set"
echo "=== Starting Apache ==="

exec apache2-foreground
