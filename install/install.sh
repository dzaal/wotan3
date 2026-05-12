#!/usr/bin/env bash
# Wotan3 — Install Script
# Run from the webroot: bash install/install.sh

set -e

WEBROOT="$(cd "$(dirname "$0")/.." && pwd)"
SCHEMA="$WEBROOT/install/schema.sql"
CONFIG="$WEBROOT/share/settings/config.php"
CONFIG_EXAMPLE="$WEBROOT/share/settings/config.example.php"

echo ""
echo "================================================="
echo "  Wotan3 Framework — Installer"
echo "================================================="
echo ""

# -------------------------------------------------------
# 1. Check PHP
# -------------------------------------------------------
if ! command -v php &>/dev/null; then
    echo "ERROR: PHP is not installed or not in PATH."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION;")
if [ "$PHP_VERSION" -lt 8 ]; then
    echo "ERROR: PHP 8.0+ is required (found PHP $PHP_VERSION)."
    exit 1
fi
echo "[OK] PHP $(php -r 'echo PHP_VERSION;') found."

# -------------------------------------------------------
# 2. Check mysql client
# -------------------------------------------------------
if ! command -v mysql &>/dev/null; then
    echo "ERROR: mysql client is not installed."
    exit 1
fi
echo "[OK] mysql client found."

# -------------------------------------------------------
# 3. Collect database credentials
# -------------------------------------------------------
echo ""
echo "--- Database setup ---"
read -rp "  MySQL host      [localhost]: " DB_HOST
DB_HOST="${DB_HOST:-localhost}"

read -rp "  MySQL port      [3306]: " DB_PORT
DB_PORT="${DB_PORT:-3306}"

read -rp "  MySQL user      [root]: " DB_USER
DB_USER="${DB_USER:-root}"

read -rsp "  MySQL password  : " DB_PASS
echo ""

read -rp "  Database name   [wotan3]: " DB_NAME
DB_NAME="${DB_NAME:-wotan3}"

# -------------------------------------------------------
# 4. Create database if it doesn't exist
# -------------------------------------------------------
echo ""
echo "Creating database '$DB_NAME' if it does not exist..."
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null \
    || { echo "ERROR: Could not connect to MySQL. Check your credentials."; exit 1; }
echo "[OK] Database ready."

# -------------------------------------------------------
# 5. Run schema
# -------------------------------------------------------
echo "Running schema.sql..."
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" < "$SCHEMA"
echo "[OK] Tables created."

# -------------------------------------------------------
# 6. Site identity
# -------------------------------------------------------
echo ""
echo "--- Site configuration ---"
read -rp "  Domain name     [example.com]: " DOMAIN
DOMAIN="${DOMAIN:-example.com}"

read -rp "  Company name    [My Company]: " COMPANY
COMPANY="${COMPANY:-My Company}"

read -rp "  Admin email     [admin@$DOMAIN]: " ADMIN_EMAIL
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@$DOMAIN}"

read -rp "  Webmaster hostname (for dev error display, leave blank to skip): " WEBMASTER
WEBMASTER="${WEBMASTER:-}"

# -------------------------------------------------------
# 7. Create users
# -------------------------------------------------------
echo ""
echo "--- Admin user ---"

# Helper: read a non-empty value
prompt_required() {
    local val=""
    while [ -z "$val" ]; do
        read -rp "  $1: " val
        [ -z "$val" ] && echo "  (required — cannot be empty)"
    done
    echo "$val"
}

# Helper: read a confirmed password (silent)
prompt_password() {
    local p1 p2
    while true; do
        read -rsp "  Password        : " p1; echo ""
        [ ${#p1} -lt 8 ] && echo "  (minimum 8 characters)" && continue
        read -rsp "  Confirm password: " p2; echo ""
        [ "$p1" = "$p2" ] && break
        echo "  (passwords do not match, try again)"
    done
    echo "$p1"
}

# Helper: bcrypt hash via PHP
bcrypt() {
    php -r "echo password_hash('$1', PASSWORD_BCRYPT);"
}

# Helper: insert one user row
insert_user() {
    local username="$1" email="$2" hash="$3" usergroup="$4" \
          admin_col="$5" access_col="$6" settings_col="$7" home_col="$8"
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" <<SQL
INSERT INTO users (username, email, password, hpassword, usergroup, admin, access, settings, home, online)
VALUES (
    '$(echo "$username" | sed "s/'/\\\\'/g")',
    '$(echo "$email"    | sed "s/'/\\\\'/g")',
    '*',
    '$(echo "$hash"     | sed "s/'/\\\\'/g")',
    '$usergroup',
    '$admin_col',
    '$access_col',
    '$settings_col',
    '$home_col',
    1
);
SQL
    echo "[OK] User '$username' ($usergroup) created."
}

# --- First admin user (required) ---
ADMIN_USER=$(prompt_required "Username")
ADMIN_PASS=$(prompt_password)
ADMIN_HASH=$(bcrypt "$ADMIN_PASS")
insert_user "$ADMIN_USER" "$ADMIN_EMAIL" "$ADMIN_HASH" "admin" "/admin/*" "/" "admin.php" "/admin/info/"

# --- Optional extra users ---
echo ""
while true; do
    read -rp "  Add another user? [y/N]: " ADD_MORE
    case "$ADD_MORE" in
        [yY]*)
            echo ""
            echo "  Usergroups: admin, editor, manage, members"
            EXTRA_USER=$(prompt_required "  Username")
            read -rp "  Email           : " EXTRA_EMAIL
            EXTRA_EMAIL="${EXTRA_EMAIL:-}"
            read -rp "  Usergroup       [members]: " EXTRA_GROUP
            EXTRA_GROUP="${EXTRA_GROUP:-members}"
            EXTRA_PASS=$(prompt_password)
            EXTRA_HASH=$(bcrypt "$EXTRA_PASS")

            # Set sensible defaults per usergroup
            case "$EXTRA_GROUP" in
                admin)
                    insert_user "$EXTRA_USER" "$EXTRA_EMAIL" "$EXTRA_HASH" "admin" "/admin/*" "/" "admin.php" "/admin/info/"
                    ;;
                editor|manage)
                    insert_user "$EXTRA_USER" "$EXTRA_EMAIL" "$EXTRA_HASH" "$EXTRA_GROUP" "/admin/*" "/" "admin.php" "/admin/info/"
                    ;;
                *)
                    insert_user "$EXTRA_USER" "$EXTRA_EMAIL" "$EXTRA_HASH" "members" "" "/" "guests.php" "/"
                    ;;
            esac
            ;;
        *)
            break
            ;;
    esac
done

# -------------------------------------------------------
# 8. Write config.php
# -------------------------------------------------------
echo ""
echo "Writing share/settings/config.php..."

cat > "$CONFIG" <<PHP
<?php
// *** Wotan3 Configuration — generated by install.sh
// Do NOT commit this file to version control.

\$mysql['host']     = '$DB_HOST';
\$mysql['user']     = '$DB_USER';
\$mysql['pass']     = '$DB_PASS';
\$mysql['selectdb'] = '$DB_NAME';

\$echo['company_name'] = '$COMPANY';
\$domainname           = '$DOMAIN';

\$webmastername = '$WEBMASTER';
\$webmasterip   = \$webmastername ? gethostbyname(\$webmastername) : '';
\$forwarded_ip  = '';
if (!empty(\$_SERVER['HTTP_X_FORWARDED_FOR'])) {
    \$forwarded_ip = trim(explode(',', \$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}
\$visitor_ips     = array_filter(array(\$_SERVER['REMOTE_ADDR'] ?? '', \$forwarded_ip));
\$is_webmaster_ip = \$webmasterip && in_array(\$webmasterip, \$visitor_ips, true);

if (\$is_webmaster_ip) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', '1');
}

\$webmastereml = 'admin@$DOMAIN';
\$clientmail   = '$ADMIN_EMAIL';
\$analytics_ID = '';
\$languages    = 'nl, en';

if (!isset(\$get['lan']) || !\$get['lan']) \$get['lan'] = \$_GET['lan'] = 'english';

\$protocol   = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
\$websiteurl = \$echo['websiteurl'] = \$protocol . \$domainname;
PHP

echo "[OK] config.php written."

# -------------------------------------------------------
# 9. File permissions
# -------------------------------------------------------
echo ""
echo "--- File permissions ---"

# Detect web server user
WEB_USER=""
for candidate in www-data apache nginx http _www; do
    if id "$candidate" &>/dev/null; then
        WEB_USER="$candidate"
        break
    fi
done

if [ -z "$WEB_USER" ]; then
    read -rp "  Web server user (e.g. www-data): " WEB_USER
fi
echo "  Web server user: $WEB_USER"

# Ensure directories exist
mkdir -p "$WEBROOT/share/tmp" "$WEBROOT/share/log"

# Set ownership and permissions
chown -R "$WEB_USER" "$WEBROOT/share/tmp" "$WEBROOT/share/log" 2>/dev/null \
    || echo "  (chown skipped — run as root to set ownership)"
chmod 770 "$WEBROOT/share/tmp" "$WEBROOT/share/log"

# config.php — readable by web server only
chmod 640 "$CONFIG"
chown "$WEB_USER" "$CONFIG" 2>/dev/null || true

echo "[OK] share/tmp/  — 770, owned by $WEB_USER"
echo "[OK] share/log/  — 770, owned by $WEB_USER"
echo "[OK] config.php  — 640"

# -------------------------------------------------------
# 10. Done
# -------------------------------------------------------
echo ""
echo "================================================="
echo "  Installation complete!"
echo ""
echo "  Browse to: http://$DOMAIN/admin/"
echo "  Login with the admin account you just created."
echo "================================================="
echo ""
