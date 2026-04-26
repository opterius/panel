## [2.5.0] - 2026-04-26

Add force webmail update button and webmail version display in Updates page

# Changelog

All notable changes to Opterius (panel + agent) are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/) and
[Semantic Versioning](https://semver.org/).

Each release ships panel and agent together under the same version number.

---

## [2.2.5] - 2026-04-21

### Added — Analytics
- **Real-time visitors** — live counter at the top of the analytics page, auto-refreshes every 30 seconds
- **Last 30-minute visit sparkline** — see traffic trends in real time
- **Hour × Day heatmap** — visualize when visitors arrive (GitHub-style grid)
- **Bandwidth over time chart** — separate from visits for clearer bandwidth tracking
- **Traffic source breakdown** — Direct / Search / Social / Referral donut chart
- **Device type breakdown** — Desktop / Mobile / Tablet donut chart
- **Top 404 broken links** — helps catch SEO-damaging dead URLs
- **Top IPs table** — spot abusive clients or misconfigured scrapers
- **Compare with previous period** — overlay dashed line on visits chart
- **Exact HTTP status codes** — individual codes like 200, 301, 404 (previously only 2xx/3xx/4xx summaries)
- **Full-width analytics layout** — makes better use of wide screens

### Added — Dashboard
- SSL + status badges next to the primary domain in the Account Info card
- New `.htaccess` management page under Advanced (previously buried in the domains list)

### Added — Branding
- Opterius logo + favicon everywhere (welcome page, auth pages, sidebars, browser tab)
- Dark-themed login / password / register pages matching the welcome page
- Email account password fields now have **Generate random password**, **Show/Hide**, and **Copy** buttons (cryptographically secure, avoids confusing characters)

### Changed
- **Brand color everywhere is now #ff6900 (Opterius orange)** — all previously indigo buttons, badges, and highlights now use the brand color
- Create-account page redesigned as a two-column layout (left: account info/domain/owner; right: package selection + summary)
- Removed redundant Domain icon from user dashboard (sidebar Domains link replaces it)
- User sidebar groups are now collapsible with auto-expand for the active page
- Account switcher: alphabetical sort, search box, scrollable list — for users with many accounts
- phpMyAdmin: multi-server accounts see a server dropdown (top button previously only reached the first server)
- phpMyAdmin top button now uses cookie auth (`?server=2`) — fixes "Missing token" error
- Single "Update Opterius" button updates panel and agent together
- Force agent re-download moved to an Advanced section for troubleshooting
- Server details page: shows "Agent connected" state with collapsible reinstall instructions when the agent is online, or a prominent install card when it's offline; agent token is now displayed as a masked copyable field

### Fixed
- Updates page shows current version in release notes title
- Update log viewer has a proper scrollable box
- Panel auto-update logs each step individually for better visibility

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
