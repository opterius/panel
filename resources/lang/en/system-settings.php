<?php

return [

    // Page header
    'page_title'    => 'System Settings',
    'page_subtitle' => 'Server-wide defaults and policies. Apply to every account on this panel installation.',

    // Sidebar category labels
    'cat_display'       => 'Display & Branding',
    'cat_domains'       => 'Domains',
    'cat_mail'          => 'Mail',
    'cat_security'      => 'Security',
    'cat_ssl'           => 'SSL',
    'cat_php'           => 'PHP',
    'cat_notifications' => 'Notifications',
    'cat_integrations'  => 'Integrations',
    'cat_system'        => 'System',

    // Common
    'save'             => 'Save changes',
    'saved'            => 'Settings saved.',
    'coming_soon'      => 'Coming soon',
    'coming_soon_text' => 'This category is part of the System Settings roadmap. The first batch ships with the Domains category and grows from there.',

    // ── Domains category ──────────────────────────────────────────
    'domains_title'    => 'Domains',
    'domains_subtitle' => 'Defaults applied when new domains and subdomains are created.',

    'default_php_version_label' => 'Default PHP version for new packages',
    'default_php_version_hint'  => 'When you create a new hosting package, this version is pre-selected as the default. Existing packages and domains are not affected.',

    // ── Display & Branding ────────────────────────────────────────
    'display_title'    => 'Display & Branding',
    'display_subtitle' => 'How the panel presents itself to your customers.',

    'panel_name_label'     => 'Panel name',
    'panel_name_hint'      => 'Shown in the browser title bar, emails, and the login screen.',
    'primary_color_label'  => 'Primary colour',
    'primary_color_hint'   => 'Accent colour for buttons and links.',
    'logo_url_label'       => 'Logo URL',
    'logo_url_hint'        => 'Full URL to your logo. Leave blank to show the panel name as text.',
    'default_locale_label' => 'Default language',
    'default_locale_hint'  => 'Language for new users. Each user can override this in their profile.',
    'items_per_page_label' => 'Rows per page',
    'items_per_page_hint'  => 'How many records to show in listings before paginating.',

    // ── Mail ──────────────────────────────────────────────────────
    'mail_title'    => 'Mail',
    'mail_subtitle' => 'Outbound mail used by the panel itself, plus per-account sending limits.',

    'from_address_label'           => 'From address',
    'from_address_hint'            => 'Sender address for password resets, alerts, and account notifications.',
    'from_name_label'              => 'From name',
    'from_name_hint'               => 'Display name shown alongside the sender address.',
    'smtp_host_label'              => 'SMTP host',
    'smtp_host_hint'               => 'Leave blank to deliver through the local mail server.',
    'smtp_port_label'              => 'SMTP port',
    'smtp_port_hint'               => 'Usually 587 for TLS or 465 for SSL.',
    'smtp_encryption_label'        => 'Encryption',
    'smtp_encryption_hint'         => 'Transport security for the SMTP connection.',
    'smtp_username_label'          => 'SMTP username',
    'smtp_username_hint'           => 'Leave blank if the relay does not require authentication.',
    'smtp_password_label'          => 'SMTP password',
    'smtp_password_hint'           => 'Leave blank to keep the stored password unchanged.',
    'max_hourly_per_account_label' => 'Hourly send limit per account',
    'max_hourly_per_account_hint'  => 'Caps outbound messages per hosting account to contain a compromised site. 0 disables the limit.',

    // ── Security ──────────────────────────────────────────────────
    'security_title'    => 'Security',
    'security_subtitle' => 'Authentication policy for panel logins.',

    'password_min_length_label' => 'Minimum password length',
    'password_min_length_hint'  => 'Applies to new passwords. Existing passwords are not invalidated.',
    'require_2fa_admins_label'  => 'Require two-factor authentication for admins',
    'require_2fa_admins_hint'   => 'Admins without 2FA are prompted to enrol at next login.',
    'session_lifetime_label'    => 'Session lifetime (minutes)',
    'session_lifetime_hint'     => 'How long an idle session stays signed in.',
    'max_login_attempts_label'  => 'Failed attempts before lockout',
    'max_login_attempts_hint'   => 'Consecutive failures from one address before it is temporarily blocked.',
    'lockout_minutes_label'     => 'Lockout duration (minutes)',
    'lockout_minutes_hint'      => 'How long a blocked address stays locked out.',
    'admin_ip_allowlist_label'  => 'Admin IP allowlist',
    'admin_ip_allowlist_hint'   => 'One address or CIDR range per line. Leave blank to allow admin logins from anywhere — recommended only if you have a static IP.',

    // ── SSL ───────────────────────────────────────────────────────
    'ssl_title'    => 'SSL',
    'ssl_subtitle' => 'Certificate issuance and renewal defaults.',

    'auto_issue_label'        => 'Auto-issue certificates for new domains',
    'auto_issue_hint'         => 'Request a Let\'s Encrypt certificate automatically when a domain is created.',
    'force_https_label'       => 'Redirect HTTP to HTTPS',
    'force_https_hint'        => 'Once a certificate exists, send plain HTTP visitors to the secure URL.',
    'le_contact_email_label'  => 'Let\'s Encrypt contact email',
    'le_contact_email_hint'   => 'Used for expiry warnings from the certificate authority.',
    'renew_days_before_label' => 'Renew this many days before expiry',
    'renew_days_before_hint'  => 'Certificates last 90 days; 30 leaves ample room to retry a failure.',

    // ── PHP ───────────────────────────────────────────────────────
    'php_title'    => 'PHP',
    'php_subtitle' => 'Default PHP-FPM limits applied to new hosting accounts.',

    'memory_limit_label'        => 'Memory limit',
    'memory_limit_hint'         => 'For example 256M or 1G.',
    'max_execution_time_label'  => 'Max execution time (seconds)',
    'max_execution_time_hint'   => 'How long a single request may run before PHP terminates it.',
    'upload_max_filesize_label' => 'Max upload file size',
    'upload_max_filesize_hint'  => 'Largest single file a customer can upload.',
    'post_max_size_label'       => 'Max POST size',
    'post_max_size_hint'        => 'Should be at least as large as the upload limit above.',
    'disable_functions_label'   => 'Disabled functions',
    'disable_functions_hint'    => 'Comma-separated PHP functions to disable. The defaults block shell execution, which is the usual route from a compromised site to a compromised server.',

    // ── Notifications ─────────────────────────────────────────────
    'notifications_title'    => 'Notifications',
    'notifications_subtitle' => 'When the panel should email you, and the thresholds that trigger alerts.',

    'admin_email_label'              => 'Administrator email',
    'admin_email_hint'               => 'Where system alerts are sent.',
    'notify_account_created_label'   => 'Email me when an account is created',
    'notify_account_created_hint'    => 'Includes accounts provisioned automatically by billing.',
    'notify_account_suspended_label' => 'Email me when an account is suspended',
    'notify_account_suspended_hint'  => 'Covers both manual and overdue-payment suspensions.',
    'disk_threshold_label'           => 'Disk usage alert threshold (%)',
    'disk_threshold_hint'            => 'Raise an alert when a server exceeds this much disk usage.',
    'load_threshold_label'           => 'Load average alert threshold',
    'load_threshold_hint'            => 'Raise an alert when the one-minute load average exceeds this value.',

    // ── System ────────────────────────────────────────────────────
    'system_title'    => 'System',
    'system_subtitle' => 'Server-wide identity and data retention.',

    'ns1_label'                    => 'Primary nameserver',
    'ns1_hint'                     => 'Hostname, not a URL. Written as an NS record into every DNS zone the panel creates.',
    'ns2_label'                    => 'Secondary nameserver',
    'ns2_hint'                     => 'Hostname of your second nameserver.',
    'panel_hostname_label'         => 'Panel hostname',
    'panel_hostname_hint'          => 'The hostname customers use to reach this panel.',
    'timezone_label'               => 'Server timezone',
    'timezone_hint'                => 'Used for scheduled tasks, logs, and displayed timestamps.',
    'backup_retention_days_label'  => 'Backup retention (days)',
    'backup_retention_days_hint'   => 'Backups older than this are pruned automatically.',
    'metrics_retention_days_label' => 'Metrics retention (days)',
    'metrics_retention_days_hint'  => 'How long per-minute server metrics are kept before pruning.',

];
