#!/bin/bash
#
# Opterius Agent Installer
# Usage: curl -sL https://get.opterius.com/agent | bash -s -- --token=YOUR_TOKEN
#
# Supports: Ubuntu 22.04, Ubuntu 24.04, Debian 12, AlmaLinux 9
#
set -euo pipefail

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
# Parse arguments
# ============================================================
TOKEN=""
LISTEN_ADDR="127.0.0.1"
PORT="7443"

for arg in "$@"; do
    case "$arg" in
        --token=*)  TOKEN="${arg#*=}" ;;
        --public)   LISTEN_ADDR="0.0.0.0" ;;
        --port=*)   PORT="${arg#*=}" ;;
        *)          warn "Unknown argument: $arg" ;;
    esac
done

[[ -z "$TOKEN" ]] && err "Usage: $0 --token=YOUR_AGENT_TOKEN [--public] [--port=7443]"
[[ $EUID -ne 0 ]] && err "This script must be run as root."

# ============================================================
# Detect OS
# ============================================================
if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    OS_ID="$ID"
    OS_VERSION="$VERSION_ID"
else
    err "Cannot detect OS."
fi

case "$OS_ID" in
    ubuntu|debian|almalinux|rocky) ;;
    *) err "Unsupported OS: $OS_ID" ;;
esac

ok "Detected OS: $OS_ID $OS_VERSION"

# ============================================================
# Detect architecture
# ============================================================
ARCH=$(uname -m)
case "$ARCH" in
    x86_64)  ARCH_SUFFIX="linux-amd64" ;;
    aarch64) ARCH_SUFFIX="linux-arm64" ;;
    *)       err "Unsupported architecture: $ARCH" ;;
esac

# ============================================================
# Configuration
# ============================================================
AGENT_BIN="/usr/local/bin/opterius-agent"
CONF_DIR="/etc/opterius"
CONF_FILE="${CONF_DIR}/agent.conf"
DOWNLOAD_URL="https://github.com/opterius/agent/releases/latest/download/opterius-agent-${ARCH_SUFFIX}"

# ============================================================
# Install
# ============================================================
info "Downloading Opterius Agent..."
curl -sL -o "$AGENT_BIN" "$DOWNLOAD_URL"
chmod +x "$AGENT_BIN"
ok "Agent binary installed to $AGENT_BIN"

# Verify
AGENT_VERSION=$("$AGENT_BIN" --version 2>&1 || echo "unknown")
info "Agent version: $AGENT_VERSION"

# ============================================================
# Configure
# ============================================================
info "Writing configuration..."
mkdir -p "$CONF_DIR"

cat > "$CONF_FILE" <<EOF
# Opterius Agent Configuration
# Managed by installer — edit carefully
secret=${TOKEN}
listen_addr=${LISTEN_ADDR}
port=${PORT}
EOF

chmod 600 "$CONF_FILE"
ok "Configuration written to $CONF_FILE"

# ============================================================
# Systemd service
# ============================================================
info "Installing systemd service..."
cat > /etc/systemd/system/opterius-agent.service <<EOF
[Unit]
Description=Opterius Agent
After=network.target

[Service]
Type=simple
ExecStart=${AGENT_BIN} --config ${CONF_FILE}
Restart=always
RestartSec=5
User=root
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now opterius-agent
ok "Agent service started"

# ============================================================
# Firewall (only if listening publicly)
# ============================================================
if [[ "$LISTEN_ADDR" == "0.0.0.0" ]]; then
    info "Opening port $PORT in firewall..."
    if command -v ufw &>/dev/null; then
        ufw allow ${PORT}/tcp >/dev/null 2>&1
    elif command -v firewall-cmd &>/dev/null; then
        firewall-cmd --permanent --add-port=${PORT}/tcp >/dev/null 2>&1
        firewall-cmd --reload >/dev/null 2>&1
    fi
    ok "Firewall updated"
fi

# ============================================================
# Install common server software
# ============================================================
info "Installing server software (Nginx, PHP, MariaDB, Certbot)..."

if [[ "$OS_ID" == "ubuntu" || "$OS_ID" == "debian" ]]; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y -qq

    # PHP PPA
    if [[ "$OS_ID" == "ubuntu" ]]; then
        add-apt-repository -y ppa:ondrej/php 2>/dev/null
    else
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg 2>/dev/null
        echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
    fi
    apt-get update -y -qq

    apt-get install -y -qq nginx mariadb-server certbot python3-certbot-nginx \
        php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml \
        php8.3-curl php8.3-mbstring php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

    systemctl enable --now nginx mariadb php8.3-fpm

else
    dnf install -y -q epel-release
    dnf install -y -q https://rpms.remirepo.net/enterprise/remi-release-9.rpm 2>/dev/null || true
    dnf install -y -q nginx mariadb-server certbot python3-certbot-nginx \
        php83-php-fpm php83-php-cli php83-php-common php83-php-mysqlnd php83-php-xml \
        php83-php-curl php83-php-mbstring php83-php-zip php83-php-gd php83-php-intl php83-php-bcmath

    systemctl enable --now nginx mariadb php83-php-fpm
fi

# Secure MariaDB
mysql -u root -e "
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
FLUSH PRIVILEGES;
" 2>/dev/null || true

ok "Server software installed"

# ============================================================
# Verify
# ============================================================
sleep 2
HEALTH=$(curl -s http://127.0.0.1:${PORT}/health 2>/dev/null || echo '{"status":"unreachable"}')

echo ""
echo -e "${GREEN}${BOLD}========================================${NC}"
echo -e "${GREEN}${BOLD}   Opterius Agent installed!${NC}"
echo -e "${GREEN}${BOLD}========================================${NC}"
echo ""
echo -e "  Status:    $(systemctl is-active opterius-agent)"
echo -e "  Listening: ${LISTEN_ADDR}:${PORT}"
echo -e "  Health:    ${HEALTH}"
echo -e "  Config:    ${CONF_FILE}"
echo ""
echo -e "  Installed: Nginx, PHP 8.3, MariaDB, Certbot"
echo ""
echo -e "${CYAN}  This server is now ready to be managed from Opterius Panel.${NC}"
echo ""
