# OPanel

**The modern web hosting control panel.** A fast, clean alternative to cPanel — fully open source, with no licence key, no account limits, and no phone-home.

OPanel is the web interface that manages hosting accounts, domains, email, databases, SSL certificates, and more. It pairs with the [OPanel Agent](https://github.com/siyamex/opanel-agent) — an open-source Go binary that executes privileged server operations.

---

## About this fork

OPanel is a fork of **Opterius Panel** by Host Server SRL, which is distributed under the GNU Affero General Public License v3.0.

**Changes made in this fork:**

- **Licence enforcement removed.** The upstream panel limited installations to 5 hosting accounts and verified a key against a central licence server on every request. `LicenseService` now reports an unlimited, always-valid licence and makes no network calls. Account, domain, and server caps are gone.
- **No telemetry.** Upstream contacted `opterius.com` and `api.ipify.org` on each licence check. Both calls are removed.
- **Open-source agent.** Upstream depends on a closed-source, licence-checked agent binary. This fork ships [OPanel Agent](https://github.com/siyamex/opanel-agent), a clean-room reimplementation of the same HMAC-signed HTTP protocol, under the same AGPLv3 licence.
- **Rebranded** from Opterius to OPanel, including config keys (`config('opanel.*')`), environment variables (`OPANEL_*`), and paths (`/etc/opanel/`).
- Fixed a Windows-only crash where `__('Profile')` resolved to a translation *file* rather than a string on case-insensitive filesystems.

---

## Features

**Hosting Management**
- Account creation with per-package quotas (disk, bandwidth, domains, databases, email)
- Domain management with subdomains, aliases, and redirects
- DNS zone management
- SSL certificates (Let's Encrypt auto-issue + custom upload)
- PHP version switching per domain (8.1, 8.2, 8.3, 8.4+)
- Apache/.htaccess support (Nginx + Apache dual-stack, per-domain toggle)

**Email** — virtual accounts with Dovecot + Postfix, webmail, forwarders, autoresponders, DKIM signing

**Databases** — MySQL/MariaDB with phpMyAdmin, PostgreSQL, per-database user management

**Files & Developer Tools** — file manager, FTP accounts, SSH keys with chrooted shell, web terminal, Git deployment, Node.js app management, Composer

**Software Installers** — WordPress, Laravel, Joomla, Drupal, Magento, PrestaShop

**Security** — directory password protection, hotlink protection, firewall (UFW), Fail2ban, malware scanning, emergency lockdown

**Business** — multi-server support, reseller accounts, support tickets, activity audit log

---

## Architecture

```
┌──────────────────────────────────────┐
│            OPanel                    │
│         (this repo — AGPLv3)         │
│                                      │
│  Laravel PHP app running as www-data │
│  Provides the web UI for all         │
│  hosting management features         │
│                                      │
│  Communicates with the agent via     │
│  HMAC-signed HTTP on 127.0.0.1:7443  │
└──────────────┬───────────────────────┘
               │ HMAC-signed HTTP
               ▼
┌──────────────────────────────────────┐
│         OPanel Agent                 │
│      (open source — AGPLv3)          │
│                                      │
│  Runs as root, executes privileged   │
│  operations: create users, write     │
│  Nginx vhosts, manage PHP-FPM,       │
│  issue SSL certs, create databases,  │
│  manage email accounts, etc.         │
│                                      │
│  No licence check. No account limit. │
└──────────────────────────────────────┘
```

The panel cannot perform server operations on its own — all privileged actions are delegated to the agent over authenticated local HTTP.

### Request signing

Every privileged call is signed:

```
payload   = timestamp + METHOD + path + body
signature = hex(hmac_sha256(payload, agent_token))
```

sent as `X-Signature` and `X-Timestamp` (RFC3339), where `path` excludes the query string. Multipart uploads sign an empty body so neither side buffers large files. The shared `agent_token` lives on the panel's `servers` record and in the agent's config.

---

## Installation

### Requirements

| Resource | Minimum | Recommended |
|---|---|---|
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| RAM | 1 GB | 2 GB+ |
| Disk | 20 GB | 40 GB+ SSD |
| PHP | 8.4 | 8.4+ |

PHP 8.4 or newer is required — the dependency lock pins Symfony 8.

### Panel

```bash
git clone https://github.com/siyamex/panel.git
cd panel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
php artisan serve
```

Then open `/setup` to create the first admin account.

### Agent

The agent must run on the Linux server it manages, as root:

```bash
git clone https://github.com/siyamex/opanel-agent.git
cd opanel-agent
go build -o opanel-agent .

sudo mkdir -p /etc/opanel
printf 'OPANEL_TOKEN=%s\n' "$(openssl rand -hex 32)" | sudo tee /etc/opanel/agent.conf
sudo ./opanel-agent
```

Put that same token in the panel's server record (Servers → Edit → Agent Token). Use `--dry-run` to log privileged commands instead of executing them.

---

## Documentation

See the [agent repository](https://github.com/siyamex/opanel-agent) for the endpoint reference and instructions on implementing additional endpoints.

---

## Contributing

1. **Fork** the repository
2. **Create a branch** (`git checkout -b feature/my-feature`)
3. **Make your changes** and test them locally
4. **Submit a pull request** with a clear description

**Code style** — follow existing Laravel conventions, use Tailwind utility classes rather than custom CSS, and use lang keys for all user-facing strings (never hardcode English in Blade templates).

---

## License

OPanel is free software under the [GNU Affero General Public License v3.0](LICENSE).

This is a modified version of Opterius Panel. Under AGPLv3 §13, if you run this
software on a network server you must offer its complete corresponding source
code to users interacting with it over that network.

Built with Laravel, Tailwind CSS, Alpine.js, and Livewire.

```
Copyright (C) 2025-2026 Host Server SRL   — original Opterius Panel
Copyright (C) 2026 OPanel contributors    — modifications described above
```

"Opterius" is a trademark of Host Server SRL. This fork is not affiliated with,
endorsed by, or supported by Host Server SRL.
