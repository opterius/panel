#!/usr/bin/env bash
#
# Install the queue workers and schedulers that OPanel and Commerce need.
#
#   sudo ./deploy/install-services.sh [--panel /var/www/opanel] [--commerce /var/www/commerce]
#
# Without these, two things silently do not happen:
#   * Commerce writes provisioning jobs to the queue and nothing consumes them,
#     so a paid order never creates a hosting account.
#   * Neither app's scheduled commands run, so renewal invoices are never
#     generated, overdue services are never suspended, and the panel never
#     collects server metrics.

set -euo pipefail

PANEL_DIR=/var/www/opanel
COMMERCE_DIR=/var/www/commerce
PHP_BIN=$(command -v php || echo /usr/bin/php)

while [[ $# -gt 0 ]]; do
    case "$1" in
        --panel)    PANEL_DIR="$2";    shift 2 ;;
        --commerce) COMMERCE_DIR="$2"; shift 2 ;;
        *) echo "unknown option: $1" >&2; exit 1 ;;
    esac
done

if [[ $EUID -ne 0 ]]; then
    echo "This installer must run as root." >&2
    exit 1
fi

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

install_worker() {
    local name=$1 dir=$2 unit=$3

    if [[ ! -d "$dir" ]]; then
        echo "==> skipping $name — $dir does not exist"
        return
    fi

    echo "==> installing $name worker ($dir)"
    sed -e "s|WorkingDirectory=.*|WorkingDirectory=$dir|" \
        -e "s|ExecStart=/usr/bin/php|ExecStart=$PHP_BIN|" \
        "$SRC_DIR/$unit" > "/etc/systemd/system/$unit"

    systemctl daemon-reload
    systemctl enable --now "$unit"
}

install_worker "panel"    "$PANEL_DIR"    opanel-queue.service
install_worker "commerce" "$COMMERCE_DIR" commerce-queue.service

# Laravel's scheduler needs one cron entry per app; it dispatches everything
# else internally from routes/console.php.
echo "==> installing schedulers"
CRON_FILE=/etc/cron.d/opanel
: > "$CRON_FILE"
echo "# Managed by opanel install-services.sh" >> "$CRON_FILE"
echo "SHELL=/bin/bash" >> "$CRON_FILE"
echo "PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" >> "$CRON_FILE"

[[ -d "$PANEL_DIR" ]] && \
    echo "* * * * * www-data cd $PANEL_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1" >> "$CRON_FILE"
[[ -d "$COMMERCE_DIR" ]] && \
    echo "* * * * * www-data cd $COMMERCE_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1" >> "$CRON_FILE"

chmod 0644 "$CRON_FILE"

echo
echo "==> done"
systemctl --no-pager --lines=0 status opanel-queue commerce-queue 2>/dev/null || true
echo
echo "Scheduler entries in $CRON_FILE:"
grep -c 'schedule:run' "$CRON_FILE" || true
