#!/bin/bash
#
# Opterius Panel Installer
# Usage: curl -sL https://get.opterius.com/install.sh -o /tmp/install.sh && bash /tmp/install.sh
#
# Supports: Ubuntu 22.04, Ubuntu 24.04, Debian 12, AlmaLinux 9
#
set -uo pipefail
# Note: we intentionally do NOT use `set -e` (exit on error) because many
# steps in the installer are best-effort (certbot, optional packages, service
# reloads) and a non-zero exit from any of them should not kill the entire
# installation. Critical steps check their own exit codes explicitly.

# ============================================================
# Colors
# ============================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC} $1"; }
ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ============================================================
# Pre-flight checks
# ============================================================
[[ $EUID -ne 0 ]] && err "This script must be run as root."

info "Starting Opterius Panel installation..."
echo ""

# ============================================================
# Detect OS
# ============================================================
if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    OS_ID="$ID"
    OS_VERSION="$VERSION_ID"
else
    err "Cannot detect OS. /etc/os-release not found."
fi

case "$OS_ID" in
    ubuntu)
        [[ "$OS_VERSION" == "22.04" || "$OS_VERSION" == "24.04" ]] || err "Unsupported Ubuntu version: $OS_VERSION. Supported: 22.04, 24.04"
        PKG_MANAGER="apt"
        ;;
    debian)
        [[ "$OS_VERSION" == "12" ]] || err "Unsupported Debian version: $OS_VERSION. Supported: 12"
        PKG_MANAGER="apt"
        ;;
    almalinux|rocky)
        [[ "${OS_VERSION%%.*}" == "9" ]] || err "Unsupported $OS_ID version: $OS_VERSION. Supported: 9"
        PKG_MANAGER="dnf"
        ;;
    *)
        err "Unsupported OS: $OS_ID. Supported: Ubuntu, Debian, AlmaLinux, Rocky Linux"
        ;;
esac

ok "Detected OS: $OS_ID $OS_VERSION"

# ============================================================
# Configuration
# ============================================================
PANEL_DIR="/opt/opterius"
AGENT_DIR="/usr/local/bin"
AGENT_CONF_DIR="/etc/opterius"
PANEL_PORT=8443
AGENT_PORT=7443
PHP_VERSION="8.4"
DB_NAME="opterius"
DB_USER="opterius"
DB_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)
AGENT_SECRET=$(openssl rand -hex 32)
GITHUB_PANEL="https://github.com/opterius/panel"
AGENT_DOWNLOAD_URL="https://get.opterius.com/agent/opterius-agent-linux-amd64"

# Detect public IP early — used in .env and Opterius Mail config
SERVER_IP=$(curl -s4 --max-time 10 ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

# ============================================================
# Prompt for admin credentials
# ============================================================
echo ""
echo -e "${BOLD}=== Panel Hostname ===${NC}"
echo "Enter the hostname clients will use to access the panel."
echo "Example: panel.yourdomain.com (must point to this server's IP)"
read -rp "Panel hostname: " PANEL_HOSTNAME

echo ""
echo -e "${BOLD}=== Please confirm ===${NC}"
echo -e "  Hostname:  ${CYAN}${PANEL_HOSTNAME}${NC}"
echo ""
read -rp "Proceed with installation? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" && "$CONFIRM" != "Y" ]]; then
    echo "Installation cancelled."
    exit 0
fi
echo ""

# ============================================================
# Step 1: System update
# ============================================================
info "Updating system packages..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y -qq
    apt-get upgrade -y -qq
else
    dnf update -y -q
fi
ok "System updated"

# ============================================================
# Step 2: Install dependencies
# ============================================================
info "Installing base dependencies..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq \
        curl wget git unzip zip software-properties-common \
        gnupg2 ca-certificates lsb-release apt-transport-https

    # Add ondrej/php PPA for Ubuntu, or sury for Debian
    if [[ "$OS_ID" == "ubuntu" ]]; then
        add-apt-repository -y ppa:ondrej/php
    else
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
        echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
    fi
    apt-get update -y -qq
else
    dnf install -y -q epel-release
    dnf install -y -q https://rpms.remirepo.net/enterprise/remi-release-9.rpm || true
    dnf install -y -q curl wget git unzip zip
fi
ok "Base dependencies installed"

# ============================================================
# Step 3: Install Nginx
# ============================================================
info "Installing Nginx..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq nginx
else
    dnf install -y -q nginx
fi
systemctl enable --now nginx
ok "Nginx installed"

# ============================================================
# Step 3b: Install Apache (backend for .htaccess support)
# Apache listens on 127.0.0.1:8080 only — Nginx is the public
# frontend. Apache is disabled by default; customers opt in
# per domain via the panel.
# ============================================================
info "Installing Apache (Nginx+Apache dual-stack for .htaccess support)..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq apache2 libapache2-mod-fcgid

    # Enable required modules
    a2enmod rewrite proxy proxy_fcgi setenvif headers expires deflate

    # Disable default site
    a2dissite 000-default.conf 2>/dev/null || true

    # Configure Apache to listen on localhost:8080 only
    cat > /etc/apache2/ports.conf <<'EOPORTS'
Listen 127.0.0.1:8080
EOPORTS

    # Global Apache settings: hide version, allow .htaccess, set sensible defaults
    cat > /etc/apache2/conf-available/opterius.conf <<'EOAPACHE'
ServerTokens Prod
ServerSignature Off

# Allow .htaccess in all VirtualHosts unless overridden
<Directory />
    AllowOverride None
    Require all denied
</Directory>

<Directory /home>
    AllowOverride All
    Options -Indexes +FollowSymLinks
    Require all granted
</Directory>
EOAPACHE
    a2enconf opterius

    # Remove the default listen directives from apache2.conf so only ports.conf applies
    sed -i 's/^Listen 80$/#Listen 80/' /etc/apache2/apache2.conf 2>/dev/null || true

    systemctl enable apache2
    # Start Apache — it will have no vhosts yet, which is fine
    systemctl start apache2

else
    # RHEL/AlmaLinux
    dnf install -y -q httpd mod_fcgid

    # Configure to listen on localhost:8080 only
    sed -i 's/^Listen 80$/Listen 127.0.0.1:8080/' /etc/httpd/conf/httpd.conf

    # Global settings
    cat > /etc/httpd/conf.d/opterius.conf <<'EOAPACHE'
ServerTokens Prod
ServerSignature Off

<Directory /home>
    AllowOverride All
    Options -Indexes +FollowSymLinks
    Require all granted
</Directory>
EOAPACHE

    systemctl enable httpd
    systemctl start httpd
fi
ok "Apache installed (listening on 127.0.0.1:8080)"

# ============================================================
# Step 4: Install PHP
# ============================================================
info "Installing PHP $PHP_VERSION..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq \
        php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql php${PHP_VERSION}-xml php${PHP_VERSION}-curl \
        php${PHP_VERSION}-mbstring php${PHP_VERSION}-zip php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl php${PHP_VERSION}-bcmath php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-fileinfo php${PHP_VERSION}-dom php${PHP_VERSION}-soap \
        php${PHP_VERSION}-imap php${PHP_VERSION}-ldap php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-opcache php${PHP_VERSION}-apcu \
        php${PHP_VERSION}-exif php${PHP_VERSION}-imagick php${PHP_VERSION}-redis \
        php${PHP_VERSION}-memcached php${PHP_VERSION}-igbinary php${PHP_VERSION}-msgpack
    systemctl enable --now php${PHP_VERSION}-fpm
else
    REMI_VER=$(echo "$PHP_VERSION" | tr -d '.')
    dnf module reset php -y -q
    dnf install -y -q \
        php${REMI_VER}-php-fpm php${REMI_VER}-php-cli php${REMI_VER}-php-common \
        php${REMI_VER}-php-mysqlnd php${REMI_VER}-php-xml php${REMI_VER}-php-curl \
        php${REMI_VER}-php-mbstring php${REMI_VER}-php-zip php${REMI_VER}-php-gd \
        php${REMI_VER}-php-intl php${REMI_VER}-php-bcmath php${REMI_VER}-php-soap \
        php${REMI_VER}-php-imap php${REMI_VER}-php-ldap php${REMI_VER}-php-pgsql \
        php${REMI_VER}-php-opcache php${REMI_VER}-php-apcu \
        php${REMI_VER}-php-exif php${REMI_VER}-php-imagick php${REMI_VER}-php-redis \
        php${REMI_VER}-php-memcached php${REMI_VER}-php-igbinary php${REMI_VER}-php-msgpack
    systemctl enable --now php${REMI_VER}-php-fpm
fi
ok "PHP $PHP_VERSION installed"

# ============================================================
# Step 5: Install MariaDB
# ============================================================
info "Installing MariaDB..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq mariadb-server mariadb-client
else
    dnf install -y -q mariadb-server mariadb
fi
systemctl enable --now mariadb

# Secure MariaDB + create database
mysql -u root <<EOSQL
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOSQL
ok "MariaDB installed and configured"

# ============================================================
# Step 5b: Install PowerDNS
# ============================================================
info "Installing PowerDNS..."
PDNS_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 24)

if [[ "$PKG_MANAGER" == "apt" ]]; then
    # Disable systemd-resolved (conflicts with PowerDNS on port 53)
    systemctl disable --now systemd-resolved 2>/dev/null || true
    # /etc/resolv.conf is normally a symlink to /run/systemd/resolve/stub-resolv.conf,
    # which is not recreated on reboot once systemd-resolved is disabled. Drop the
    # symlink, write a real file, and chattr +i so nothing rewrites it on next boot.
    chattr -i /etc/resolv.conf 2>/dev/null || true
    rm -f /etc/resolv.conf
    cat > /etc/resolv.conf <<'EOF_RESOLV'
nameserver 8.8.8.8
nameserver 1.1.1.1
EOF_RESOLV
    chattr +i /etc/resolv.conf 2>/dev/null || true

    apt-get install -y -qq pdns-server pdns-backend-mysql
else
    dnf install -y -q pdns pdns-backend-mysql
fi

# Create PowerDNS database and user
mysql -u root <<EOSQL_PDNS
CREATE DATABASE IF NOT EXISTS pdns CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'pdns'@'localhost' IDENTIFIED BY '${PDNS_PASS}';
ALTER USER 'pdns'@'localhost' IDENTIFIED BY '${PDNS_PASS}';
GRANT ALL PRIVILEGES ON pdns.* TO 'pdns'@'localhost';
FLUSH PRIVILEGES;

USE pdns;

CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(6) NOT NULL DEFAULT 'NATIVE',
    notified_serial INT DEFAULT NULL,
    account VARCHAR(40) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(10) NOT NULL,
    content TEXT NOT NULL,
    ttl INT DEFAULT 3600,
    prio INT DEFAULT 0,
    disabled TINYINT(1) DEFAULT 0,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_records_domain_id ON records(domain_id);
CREATE INDEX IF NOT EXISTS idx_records_name_type ON records(name, type);
EOSQL_PDNS

# Configure PowerDNS
cat > /etc/powerdns/pdns.conf <<EOPDNS
launch=gmysql
gmysql-host=127.0.0.1
gmysql-port=3306
gmysql-dbname=pdns
gmysql-user=pdns
gmysql-password=${PDNS_PASS}
local-address=0.0.0.0
local-port=53
setgid=pdns
setuid=pdns
EOPDNS

systemctl enable --now pdns
ok "PowerDNS installed and configured"

# ============================================================
# Step 5c: Install Postfix + Dovecot (Mail Server)
# ============================================================
info "Installing mail server (Postfix + Dovecot)..."

# Create vmail user for virtual mailboxes
groupadd -g 5000 vmail 2>/dev/null || true
useradd -u 5000 -g 5000 -d /var/mail/vdomains -s /usr/sbin/nologin vmail 2>/dev/null || true
mkdir -p /var/mail/vdomains
chown -R vmail:vmail /var/mail/vdomains

if [[ "$PKG_MANAGER" == "apt" ]]; then
    # Preconfigure Postfix to avoid interactive prompts
    debconf-set-selections <<< "postfix postfix/main_mailer_type select Internet Site"
    debconf-set-selections <<< "postfix postfix/mailname string $(hostname -f)"
    apt-get install -y -qq postfix dovecot-core dovecot-imapd dovecot-pop3d dovecot-lmtpd
else
    dnf install -y -q postfix dovecot
fi

# Create virtual mailbox and domain files
touch /etc/postfix/vmailbox
touch /etc/postfix/vdomains
touch /etc/dovecot/users

# Configure Postfix for virtual domains
postconf -e "myhostname = ${PANEL_HOSTNAME}"
postconf -e "mydomain = ${PANEL_HOSTNAME}"
postconf -e "myorigin = \$mydomain"
postconf -e "inet_interfaces = all"
postconf -e "mydestination = localhost"
postconf -e "virtual_mailbox_domains = hash:/etc/postfix/vdomains"
postconf -e "virtual_mailbox_base = /var/mail/vdomains"
postconf -e "virtual_mailbox_maps = hash:/etc/postfix/vmailbox"
postconf -e "virtual_uid_maps = static:5000"
postconf -e "virtual_gid_maps = static:5000"
postconf -e "virtual_transport = lmtp:unix:private/dovecot-lmtp"
postconf -e "smtpd_tls_cert_file = /etc/ssl/opterius/panel.crt"
postconf -e "smtpd_tls_key_file = /etc/ssl/opterius/panel.key"
postconf -e "smtpd_tls_security_level = may"
postconf -e "smtpd_sasl_type = dovecot"
postconf -e "smtpd_sasl_path = private/auth"
postconf -e "smtpd_sasl_auth_enable = yes"
postconf -e "smtpd_recipient_restrictions = permit_sasl_authenticated,permit_mynetworks,reject_unauth_destination"
postconf -e "message_size_limit = 52428800"

# Build Postfix maps
postmap /etc/postfix/vmailbox
postmap /etc/postfix/vdomains

# Configure Dovecot
cat > /etc/dovecot/conf.d/10-auth.conf <<'EODOVE'
disable_plaintext_auth = no
auth_mechanisms = plain login
passdb {
    driver = passwd-file
    args = /etc/dovecot/users
}
userdb {
    driver = passwd-file
    args = /etc/dovecot/users
    default_fields = uid=5000 gid=5000 home=/var/mail/vdomains/%d/%n
}
EODOVE

cat > /etc/dovecot/conf.d/10-mail.conf <<'EODOVE'
mail_location = maildir:/var/mail/vdomains/%d/%n
namespace inbox {
    inbox = yes
}
mail_uid = 5000
mail_gid = 5000
first_valid_uid = 5000
last_valid_uid = 5000
EODOVE

cat > /etc/dovecot/conf.d/10-master.conf <<'EODOVE'
service imap-login {
    inet_listener imap {
        port = 143
    }
    inet_listener imaps {
        port = 993
        ssl = yes
    }
}

service pop3-login {
    inet_listener pop3 {
        port = 110
    }
    inet_listener pop3s {
        port = 995
        ssl = yes
    }
}

service lmtp {
    unix_listener /var/spool/postfix/private/dovecot-lmtp {
        mode = 0600
        user = postfix
        group = postfix
    }
}

service auth {
    unix_listener /var/spool/postfix/private/auth {
        mode = 0660
        user = postfix
        group = postfix
    }
}
EODOVE

cat > /etc/dovecot/conf.d/10-ssl.conf <<EODOVE
ssl = yes
ssl_cert = </etc/ssl/opterius/panel.crt
ssl_key = </etc/ssl/opterius/panel.key
ssl_min_protocol = TLSv1.2
EODOVE

# Enable and start services
systemctl enable --now postfix
systemctl enable --now dovecot

ok "Mail server installed (Postfix + Dovecot)"


# ============================================================
# Step 5e: Install phpMyAdmin
# ============================================================
info "Installing phpMyAdmin..."

PMA_DIR="/var/www/phpmyadmin"
PMA_VERSION="5.2.2"
PMA_BLOWFISH=$(openssl rand -base64 32 | head -c 32)

rm -rf ${PMA_DIR} /var/www/phpMyAdmin-${PMA_VERSION}-all-languages
curl -sL "https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz" \
    | tar -xz -C /var/www/
mv /var/www/phpMyAdmin-${PMA_VERSION}-all-languages ${PMA_DIR}

# Configure phpMyAdmin
cat > ${PMA_DIR}/config.inc.php <<EOPMA
<?php
\$cfg['blowfish_secret'] = '${PMA_BLOWFISH}';
\$cfg['Servers'][1]['auth_type'] = 'cookie';
\$cfg['Servers'][1]['host'] = 'localhost';
\$cfg['Servers'][1]['compress'] = false;
\$cfg['Servers'][1]['AllowNoPassword'] = false;
\$cfg['UploadDir'] = '';
\$cfg['SaveDir'] = '';
\$cfg['TempDir'] = '${PMA_DIR}/tmp';
\$cfg['DefaultLang'] = 'en';
EOPMA

mkdir -p ${PMA_DIR}/tmp
chown -R www-data:www-data ${PMA_DIR}
chmod -R 755 ${PMA_DIR}
chmod 770 ${PMA_DIR}/tmp

# Configure Nginx for phpMyAdmin (port 8081)
if [[ "$PKG_MANAGER" == "apt" ]]; then
    PMA_VHOST="/etc/nginx/sites-available/phpmyadmin.conf"
else
    PMA_VHOST="/etc/nginx/conf.d/phpmyadmin.conf"
fi

cat > ${PMA_VHOST} <<EONGINX_PMA
server {
    listen 8081;
    listen [::]:8081;
    server_name _;

    root ${PMA_DIR};
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${RC_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }
}
EONGINX_PMA

if [[ "$PKG_MANAGER" == "apt" ]]; then
    ln -sf /etc/nginx/sites-available/phpmyadmin.conf /etc/nginx/sites-enabled/
fi

nginx -t && systemctl reload nginx

ok "phpMyAdmin installed (port 8081)"

# ============================================================
# Step 6: Install Certbot
# ============================================================
info "Installing Certbot..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get install -y -qq certbot python3-certbot-nginx
else
    dnf install -y -q certbot python3-certbot-nginx
fi
# Enable auto-renewal timer
systemctl enable --now certbot.timer

# Auto-reload Nginx after certificate renewal
mkdir -p /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh <<'EOHOOK'
#!/bin/bash
systemctl reload nginx
EOHOOK
chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh

ok "Certbot installed (auto-renewal enabled)"

# ============================================================
# Step 7: Install Composer
# ============================================================
info "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ok "Composer installed"

# ============================================================
# Step 7b: Install Opterius Mail (webmail) — needs Composer
# ============================================================
info "Installing Opterius Mail webmail..."

MAIL_DIR="/opt/opterius-mail"
MAIL_DB="opterius_mail"
MAIL_DB_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 24)
MAIL_APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
MAIL_SSO_SECRET=$(openssl rand -hex 32)

# Clone Opterius Mail (remove any leftover from a previous run)
rm -rf ${MAIL_DIR}
git clone --depth=1 https://github.com/opterius/mail.git ${MAIL_DIR}
git config --global --add safe.directory ${MAIL_DIR}

# Create database
mysql -u root <<EOSQL_MAIL
CREATE DATABASE IF NOT EXISTS ${MAIL_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MAIL_DB}'@'localhost' IDENTIFIED BY '${MAIL_DB_PASS}';
ALTER USER '${MAIL_DB}'@'localhost' IDENTIFIED BY '${MAIL_DB_PASS}';
GRANT ALL PRIVILEGES ON ${MAIL_DB}.* TO '${MAIL_DB}'@'localhost';
FLUSH PRIVILEGES;
EOSQL_MAIL

# Install Composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction --working-dir=${MAIL_DIR}

# Write .env
cat > ${MAIL_DIR}/.env <<EOENV_MAIL
APP_NAME="Opterius Mail"
APP_ENV=production
APP_KEY=${MAIL_APP_KEY}
APP_DEBUG=false
APP_URL=http://${SERVER_IP}:8090

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${MAIL_DB}
DB_USERNAME=${MAIL_DB}
DB_PASSWORD=${MAIL_DB_PASS}

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

IMAP_HOST=127.0.0.1
IMAP_PORT=143
IMAP_ENCRYPTION=none
IMAP_VALIDATE_CERT=false
IMAP_TIMEOUT=15

SMTP_HOST=127.0.0.1
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_VALIDATE_CERT=false

MAIL_ADMIN=false

MAIL_UI_TEMPLATE=default

PANEL_SSO_ENABLED=true
PANEL_SSO_SECRET=${MAIL_SSO_SECRET}
EOENV_MAIL

# Run migrations
php ${MAIL_DIR}/artisan migrate --force

# Set permissions
chown -R www-data:www-data ${MAIL_DIR} 2>/dev/null || chown -R nginx:nginx ${MAIL_DIR}
chmod -R 755 ${MAIL_DIR}
chmod -R 775 ${MAIL_DIR}/storage ${MAIL_DIR}/bootstrap/cache

# Determine PHP-FPM socket
if [[ "$PKG_MANAGER" == "apt" ]]; then
    MAIL_FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
else
    MAIL_FPM_SOCK="/run/php-fpm/www.sock"
fi

# Configure Nginx for Opterius Mail (webmail on port 8090)
if [[ "$PKG_MANAGER" == "apt" ]]; then
    MAIL_VHOST="/etc/nginx/sites-available/opterius-mail.conf"
else
    MAIL_VHOST="/etc/nginx/conf.d/opterius-mail.conf"
fi

cat > ${MAIL_VHOST} <<EONGINX_MAIL
server {
    listen 8090;
    listen [::]:8090;
    server_name _;

    root ${MAIL_DIR}/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${MAIL_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }
}
EONGINX_MAIL

# Enable site (remove old roundcube config if present)
rm -f /etc/nginx/sites-enabled/roundcube.conf /etc/nginx/conf.d/roundcube.conf
if [[ "$PKG_MANAGER" == "apt" ]]; then
    ln -sf /etc/nginx/sites-available/opterius-mail.conf /etc/nginx/sites-enabled/
fi

nginx -t && systemctl reload nginx

ok "Opterius Mail webmail installed (port 8090)"

# ============================================================
# Step 7c: Install WP-CLI
# ============================================================
info "Installing WP-CLI..."
curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
chmod +x /usr/local/bin/wp
ok "WP-CLI installed"

# ============================================================
# Step 8: Install Node.js (for Vite/Tailwind build)
# ============================================================
info "Installing Node.js..."
if [[ "$PKG_MANAGER" == "apt" ]]; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
else
    curl -fsSL https://rpm.nodesource.com/setup_22.x | bash -
    dnf install -y -q nodejs
fi
ok "Node.js $(node --version) installed"

# ============================================================
# Step 9: Install Laravel Panel
# ============================================================
info "Installing Opterius Panel to $PANEL_DIR..."
rm -rf "$PANEL_DIR"
git clone --depth 1 "$GITHUB_PANEL" "$PANEL_DIR"

cd "$PANEL_DIR"
composer install --no-dev --optimize-autoloader --quiet

# Generate .env
cp .env.example .env
sed -i "s|APP_URL=.*|APP_URL=https://${SERVER_IP}:${PANEL_PORT}|" .env
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sed -i "s|# DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|# DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|# DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|APP_NAME=.*|APP_NAME=\"Opterius Panel\"|" .env
sed -i "s|OPTERIUS_AGENT_SECRET=.*|OPTERIUS_AGENT_SECRET=${AGENT_SECRET}|" .env 2>/dev/null || echo "OPTERIUS_AGENT_SECRET=${AGENT_SECRET}" >> .env

php artisan key:generate --force
php artisan migrate --force

# Wire up Opterius Mail SSO into the Panel .env (MAIL_SSO_SECRET set in Step 7b)
if [[ -n "${MAIL_SSO_SECRET:-}" ]]; then
    if grep -q "OPTERIUS_WEBMAIL_SSO_SECRET" .env; then
        sed -i "s|OPTERIUS_WEBMAIL_SSO_SECRET=.*|OPTERIUS_WEBMAIL_SSO_SECRET=${MAIL_SSO_SECRET}|" .env
    else
        echo "OPTERIUS_WEBMAIL_SSO_SECRET=${MAIL_SSO_SECRET}" >> .env
    fi
    if grep -q "OPTERIUS_WEBMAIL_URL" .env; then
        sed -i "s|OPTERIUS_WEBMAIL_URL=.*|OPTERIUS_WEBMAIL_URL=http://127.0.0.1:8090|" .env
    else
        echo "OPTERIUS_WEBMAIL_URL=http://127.0.0.1:8090" >> .env
    fi
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm ci --silent
npm run build

# Admin account is created via the web setup wizard on first browser visit.
# No CLI account creation needed.

# Set ownership
chown -R www-data:www-data "$PANEL_DIR" 2>/dev/null || chown -R nginx:nginx "$PANEL_DIR"
chmod -R 775 "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache"

# Install Laravel scheduler cron — runs every minute and triggers all
# scheduled commands (monitor:collect, alerts:check, etc). Without this,
# server stats and alert checks would never accumulate.
cat > /etc/cron.d/opterius <<EOCRON
# Opterius Panel — Laravel scheduler tick
# Runs every minute and dispatches all scheduled commands.
* * * * * root cd ${PANEL_DIR} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
EOCRON
chmod 644 /etc/cron.d/opterius

ok "Opterius Panel installed"

# ============================================================
# Step 10: Configure Nginx for Panel
# ============================================================
info "Configuring Nginx for panel on port $PANEL_PORT..."

# Generate self-signed SSL first (fallback)
mkdir -p /etc/ssl/opterius
openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -keyout /etc/ssl/opterius/panel.key \
    -out /etc/ssl/opterius/panel.crt \
    -subj "/CN=${PANEL_HOSTNAME}" 2>/dev/null

PANEL_SSL_CERT="/etc/ssl/opterius/panel.crt"
PANEL_SSL_KEY="/etc/ssl/opterius/panel.key"

# Try to get Let's Encrypt cert for panel hostname
if [[ -n "$PANEL_HOSTNAME" ]]; then
    info "Requesting Let's Encrypt certificate for ${PANEL_HOSTNAME}..."

    # Create a temporary Nginx config for HTTP validation
    cat > /etc/nginx/sites-available/${PANEL_HOSTNAME}.conf <<EOTMP
server {
    listen 80;
    server_name ${PANEL_HOSTNAME};
    root /var/www/html;
}
EOTMP
    ln -sf /etc/nginx/sites-available/${PANEL_HOSTNAME}.conf /etc/nginx/sites-enabled/ 2>/dev/null
    nginx -t && systemctl reload nginx

    # Only attempt Let's Encrypt if the hostname resolves to this server's IP.
    # On fresh servers the hostname usually doesn't have DNS yet, so we skip
    # silently and use the self-signed cert. The admin can issue a real cert
    # later from the panel's SSL page once DNS is configured.
    SERVER_IP=$(curl -s4 ifconfig.me 2>/dev/null || echo "")
    HOSTNAME_IP=$(dig +short "${PANEL_HOSTNAME}" A 2>/dev/null | head -1)

    if [[ -n "$SERVER_IP" && "$SERVER_IP" == "$HOSTNAME_IP" ]]; then
        certbot certonly --nginx --non-interactive --agree-tos \
            --register-unsafely-without-email -d "${PANEL_HOSTNAME}" 2>/dev/null

        if [[ -f "/etc/letsencrypt/live/${PANEL_HOSTNAME}/fullchain.pem" ]]; then
            PANEL_SSL_CERT="/etc/letsencrypt/live/${PANEL_HOSTNAME}/fullchain.pem"
            PANEL_SSL_KEY="/etc/letsencrypt/live/${PANEL_HOSTNAME}/privkey.pem"
            ok "Let's Encrypt certificate obtained for ${PANEL_HOSTNAME}"
        fi
    fi

    # Remove temporary config
    rm -f /etc/nginx/sites-available/${PANEL_HOSTNAME}.conf
    rm -f /etc/nginx/sites-enabled/${PANEL_HOSTNAME}.conf
fi

# Determine PHP-FPM socket path
if [[ "$PKG_MANAGER" == "apt" ]]; then
    FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
else
    FPM_SOCK="/run/php-fpm/www.sock"
fi

# If we have a valid Let's Encrypt cert, use HTTPS. Otherwise serve HTTP
# so the admin can access the panel immediately without browser SSL warnings.
# Once they configure a hostname with DNS, they can enable HTTPS from the panel.
if [[ -f "/etc/letsencrypt/live/${PANEL_HOSTNAME}/fullchain.pem" ]]; then
    PANEL_SSL_BLOCK="
    listen ${PANEL_PORT} ssl;
    listen [::]:${PANEL_PORT} ssl;
    ssl_certificate     /etc/letsencrypt/live/${PANEL_HOSTNAME}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${PANEL_HOSTNAME}/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;"
    PANEL_SCHEME="https"
else
    PANEL_SSL_BLOCK="
    listen ${PANEL_PORT};
    listen [::]:${PANEL_PORT};"
    PANEL_SCHEME="http"
fi

# On Debian/Ubuntu use sites-available + symlink; on RHEL/Rocky write directly to conf.d
if [[ "$PKG_MANAGER" == "apt" ]]; then
    NGINX_VHOST="/etc/nginx/sites-available/opterius-panel.conf"
    mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled
else
    NGINX_VHOST="/etc/nginx/conf.d/opterius-panel.conf"
fi

cat > ${NGINX_VHOST} <<EONGINX
server {
    ${PANEL_SSL_BLOCK}
    server_name ${PANEL_HOSTNAME} _;

    root ${PANEL_DIR}/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EONGINX

# Enable site (Debian/Ubuntu only — symlink into sites-enabled)
if [[ "$PKG_MANAGER" == "apt" ]]; then
    ln -sf /etc/nginx/sites-available/opterius-panel.conf /etc/nginx/sites-enabled/
fi

nginx -t && systemctl reload nginx
ok "Nginx configured for panel"

# ============================================================
# Step 11: Install Go Agent
# ============================================================
info "Installing Opterius Agent..."

mkdir -p "$AGENT_CONF_DIR"

# Download agent binary
curl -sL -o "${AGENT_DIR}/opterius-agent" "$AGENT_DOWNLOAD_URL"
chmod +x "${AGENT_DIR}/opterius-agent"

# Write agent config
cat > "${AGENT_CONF_DIR}/agent.conf" <<EOCONF
# Opterius Agent Configuration
secret=${AGENT_SECRET}
listen_addr=127.0.0.1
port=${AGENT_PORT}
EOCONF

# Install systemd service
cat > /etc/systemd/system/opterius-agent.service <<EOSERVICE
[Unit]
Description=Opterius Agent
After=network.target

[Service]
Type=simple
ExecStart=${AGENT_DIR}/opterius-agent --config ${AGENT_CONF_DIR}/agent.conf
Restart=always
RestartSec=5
User=root
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
EOSERVICE

systemctl daemon-reload
systemctl enable --now opterius-agent
ok "Opterius Agent installed and running"

# ============================================================
# Step 12: Firewall
# ============================================================
info "Configuring firewall..."
if command -v ufw &>/dev/null; then
    ufw allow ${PANEL_PORT}/tcp >/dev/null 2>&1
    ufw allow 80/tcp >/dev/null 2>&1
    ufw allow 443/tcp >/dev/null 2>&1
    ufw allow 25/tcp >/dev/null 2>&1
    ufw allow 587/tcp >/dev/null 2>&1
    ufw allow 993/tcp >/dev/null 2>&1
    ufw allow 995/tcp >/dev/null 2>&1
    ufw allow 143/tcp >/dev/null 2>&1
    ufw allow 110/tcp >/dev/null 2>&1
    ufw allow 53/tcp >/dev/null 2>&1
    ufw allow 53/udp >/dev/null 2>&1
    ufw allow 8080/tcp >/dev/null 2>&1
    ufw allow 8081/tcp >/dev/null 2>&1
    ufw --force enable >/dev/null 2>&1
    ok "UFW configured"
elif command -v firewall-cmd &>/dev/null; then
    firewall-cmd --permanent --add-port=${PANEL_PORT}/tcp >/dev/null 2>&1
    firewall-cmd --permanent --add-service=http >/dev/null 2>&1
    firewall-cmd --permanent --add-service=https >/dev/null 2>&1
    firewall-cmd --permanent --add-service=smtp >/dev/null 2>&1
    firewall-cmd --permanent --add-service=smtps >/dev/null 2>&1
    firewall-cmd --permanent --add-service=imap >/dev/null 2>&1
    firewall-cmd --permanent --add-service=imaps >/dev/null 2>&1
    firewall-cmd --permanent --add-service=pop3 >/dev/null 2>&1
    firewall-cmd --permanent --add-service=pop3s >/dev/null 2>&1
    firewall-cmd --permanent --add-service=dns >/dev/null 2>&1
    firewall-cmd --reload >/dev/null 2>&1
    ok "firewalld configured"
else
    warn "No firewall detected. Make sure port $PANEL_PORT is open."
fi

# ============================================================
# Done
# ============================================================
SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}${BOLD}========================================${NC}"
echo -e "${GREEN}${BOLD}   Opterius Panel installed!${NC}"
echo -e "${GREEN}${BOLD}========================================${NC}"
echo ""
echo -e "  Panel URL:    ${CYAN}${PANEL_SCHEME}://${SERVER_IP}:${PANEL_PORT}${NC}"
echo -e "  Webmail:      ${CYAN}http://${SERVER_IP}:8090${NC}"
echo -e "  phpMyAdmin:   ${CYAN}http://${SERVER_IP}:8081${NC}"
echo -e "  Hostname:     ${CYAN}${PANEL_HOSTNAME}${NC} (point DNS to this IP for HTTPS)"
echo ""
echo -e "  Open the Panel URL in your browser to create your admin account."
echo ""
echo -e "  Agent status: $(systemctl is-active opterius-agent)"
echo -e "  Cron status:  $([ -f /etc/cron.d/opterius ] && echo 'installed' || echo 'MISSING')"
echo ""
if [[ "$PANEL_SCHEME" == "http" ]]; then
    echo -e "${YELLOW}  Note: The panel is running over HTTP. To enable HTTPS, point${NC}"
    echo -e "${YELLOW}  your hostname DNS to this server and issue an SSL certificate.${NC}"
fi
echo ""
echo -e "  Documentation: ${CYAN}https://opterius.com/docs${NC}"
echo -e "  GitHub:        ${CYAN}https://github.com/opterius/panel${NC}"
echo ""
echo ""
read -rp "  Would you like to star Opterius on GitHub? (y/n): " STAR_ANSWER
if [[ "$STAR_ANSWER" == "y" || "$STAR_ANSWER" == "Y" ]]; then
    echo ""
    echo -e "  ${CYAN}Thanks! Open this link in your browser:${NC}"
    echo -e "  ${BOLD}https://github.com/opterius/panel${NC}"
fi
echo ""
