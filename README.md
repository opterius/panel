# Opterius Panel

**The modern web hosting control panel.** A fast, clean alternative to cPanel — built for hosting companies who want full control without the legacy price tag.

Opterius Panel is the open-source web interface that manages hosting accounts, domains, email, databases, SSL certificates, and more. It communicates with the [Opterius Agent](https://opterius.com) (a separate closed-source binary) that executes privileged server operations.

---

## Features

**Hosting Management**
- Account creation with per-package quotas (disk, bandwidth, domains, databases, email)
- Domain management with subdomains, aliases, and redirects
- DNS zone management (PowerDNS)
- SSL certificates (Let's Encrypt auto-issue + custom upload)
- PHP version switching per domain (8.1, 8.2, 8.3, 8.4+)
- Apache/.htaccess support (Nginx + Apache dual-stack, per-domain toggle)

**Email**
- Virtual email accounts with Dovecot + Postfix
- Roundcube webmail integration
- Email forwarders and autoresponders
- DKIM signing and deliverability setup

**Databases**
- MySQL/MariaDB management with phpMyAdmin
- PostgreSQL support
- Per-database user management with password reveal

**Files & Developer Tools**
- File Manager with archive/extract support
- FTP accounts (ProFTPD)
- SSH key management with chrooted shell (Jailkit)
- Web Terminal (browser-based SSH)
- Git deployment (clone, pull with token support)
- Node.js app management (PM2 integration)
- Composer dependency management

**Software Installers**
- WordPress (one-click install + update + security scan)
- Laravel, Joomla, Drupal, Magento, PrestaShop

**Security**
- Directory password protection (HTTP Basic Auth)
- Hotlink protection
- Firewall management (UFW)
- Fail2ban integration
- Malware scanning
- Emergency lockdown mode

**Performance**
- CDN integration (BunnyCDN — one-click per-domain)
- Visitor analytics (privacy-friendly, log-based, no cookies)

**Advanced**
- cPanel backup import (files, databases, email with original passwords, subdomains, DNS, SSL, cron)
- One-click staging environments
- Visual cron builder with run history
- Real-time error log streaming
- Point-in-time file restore from backups
- Server monitoring with historical metrics
- Backup management with per-file restore

**Business**
- Multi-server support
- Reseller accounts with custom packages
- Support ticket system
- Activity audit log

---

## Architecture

```
┌──────────────────────────────────────┐
│         Opterius Panel               │
│     (this repo — open source)        │
│                                      │
│  Laravel PHP app running as www-data │
│  Provides the web UI for all         │
│  hosting management features         │
│                                      │
│  Communicates with the agent via     │
│  HMAC-signed HTTP on 127.0.0.1:7443 │
└──────────────┬───────────────────────┘
               │ HMAC-signed HTTP
               ▼
┌──────────────────────────────────────┐
│         Opterius Agent               │
│     (closed source — Go binary)      │
│                                      │
│  Runs as root, executes privileged   │
│  operations: create users, write     │
│  Nginx vhosts, manage PHP-FPM,      │
│  issue SSL certs, create databases,  │
│  manage email accounts, etc.         │
│                                      │
│  License-checked — blocks writes     │
│  without a valid license key         │
└──────────────────────────────────────┘
```

The panel cannot perform any server operations on its own. All privileged actions are delegated to the agent via authenticated API calls.

---

## Installation

SSH into a fresh server (Ubuntu 22.04/24.04, Debian 12, or AlmaLinux 9) as root and run:

```bash
curl -sL https://get.opterius.com/install.sh -o /tmp/install.sh
bash /tmp/install.sh
```

The installer sets up everything automatically: Nginx, PHP-FPM, MariaDB, Dovecot, Postfix, PowerDNS, Roundcube, phpMyAdmin, Certbot, Node.js, the panel, and the agent.

### System Requirements

| Resource | Minimum | Recommended |
|---|---|---|
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| RAM | 1 GB | 2 GB+ |
| Disk | 20 GB | 40 GB+ SSD |
| CPU | 1 vCPU | 2 vCPUs |

---

## Free Plan

Opterius Panel is **free for up to 5 hosting accounts** with all features enabled. No credit card required. No feature restrictions.

Paid plans unlock higher account limits for hosting companies managing more servers and customers. See [opterius.com/pricing](https://opterius.com/pricing) for details.

---

## Documentation

Full documentation is available at [opterius.com/docs](https://opterius.com/docs/en), covering:

- Getting started and installation
- Account and domain management
- Email, DNS, SSL configuration
- cPanel migration guide
- Developer tools (Git, Node.js, Composer)
- Security features
- API reference

---

## Contributing

Contributions are welcome! Please read the guidelines below before submitting.

### How to contribute

1. **Fork** the repository
2. **Create a branch** for your change (`git checkout -b feature/my-feature`)
3. **Make your changes** and test them locally
4. **Submit a pull request** with a clear description of what you changed and why

### What we accept

- Bug fixes
- UI/UX improvements
- Translations (see `resources/lang/`)
- Documentation improvements
- Performance optimizations

### What we don't accept

- Changes that require modifications to the closed-source agent
- Features that bypass the license check
- Breaking changes to the agent communication protocol

### Code style

- Follow the existing Laravel conventions
- Use the existing Tailwind CSS utility classes — no custom CSS unless necessary
- Use lang keys for all user-facing strings (never hardcode English text in Blade templates)

---

## License

Opterius Panel is open source under the [GNU Affero General Public License v3.0](LICENSE).

The Opterius Agent is a separate closed-source binary distributed under a commercial license by Host Server SRL. A free license is available for personal use and small businesses (up to 5 hosting accounts).

---

## Links

- **Website**: [opterius.com](https://opterius.com)
- **Documentation**: [opterius.com/docs](https://opterius.com/docs/en)
- **Pricing**: [opterius.com/pricing](https://opterius.com/pricing)
- **Support**: [opterius.com/dashboard/tickets](https://opterius.com/dashboard/tickets)

---

Built with Laravel, Tailwind CSS, Alpine.js, and Livewire.

Copyright (C) 2025-2026 Host Server SRL. All rights reserved.
