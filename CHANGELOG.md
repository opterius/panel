# Changelog

All notable changes to Opterius (panel + agent) are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/) and
[Semantic Versioning](https://semver.org/).

Each release ships panel and agent together under the same version number.

---

## [2.2.3] - 2026-04-21

### Added
- Automatic panel auto-update — runs daily at 03:00, no manual SSH required
- Unified release script (`release.sh`) — one command bumps both panel and agent
- Changelog is now sourced from `CHANGELOG.md` in the panel repo

### Fixed
- Nginx now redirects plain HTTP to HTTPS on the panel port instead of returning 400
- Browser auto-fill no longer overwrites the admin panel password when editing an account owner
- Admin users bypass the per-license account cap (enforced only for regular users)
- Subdomain document root default path uses the account home directory correctly

### Removed
- Agent IP-based account-limit bypass (security: bypass now enforced only at the panel level for admins)

---

## [2.2.2] - 2026-04-15

### Added
- Force agent update button + live update log viewer in the admin Updates page
- phpMyAdmin SSO — one-click login directly into the selected database

### Fixed
- Return 404 instead of 403 for unauthorized email account access (reduces information disclosure)

---

## [2.1.1] - 2026-04-01

### Added
- First public release, open-source under AGPL-3.0
- cPanel backup import (files, databases, email with original passwords, subdomains, DNS, SSL, cron)
- Customer self-service cPanel import
- Visual cron builder with run history
- Real-time error log streaming
- Point-in-time file restore from backups
- One-click staging environments
- CDN integration (BunnyCDN)
- Visitor analytics (privacy-friendly, log-based, no cookies)
- Directory password protection + hotlink protection
- Server monitoring with historical metrics (1h/6h/24h/7d/30d)
- Agent auto-update (self-updates every 12 hours)
- SSL certificate delete + re-issue
- PHP version switching per domain
- Apache/.htaccess support (per-domain toggle)
- Reseller accounts with custom packages
