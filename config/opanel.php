<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported PHP Versions
    |--------------------------------------------------------------------------
    |
    | PHP versions available for hosting accounts. The 'default' version is
    | pre-selected when creating new packages and accounts. In the future,
    | this will be auto-detected by the Go agent from installed versions.
    |
    */

    'php_versions' => ['8.2', '8.3', '8.4', '8.5'],

    'default_php_version' => '8.4',

    /*
    |--------------------------------------------------------------------------
    | Agent Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the Go agent running on managed servers.
    | The agent_port is used when auto-generating the agent URL.
    |
    */

    'agent_port' => env('OPANEL_AGENT_PORT', 7443),
    'agent_timeout' => env('OPANEL_AGENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Licensing
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Nameservers
    |--------------------------------------------------------------------------
    */

    'ns1' => env('OPANEL_NS1', 'ns1.github.com/siyamex/panel'),
    'ns2' => env('OPANEL_NS2', 'ns2.github.com/siyamex/panel'),

    /*
    |--------------------------------------------------------------------------
    | Webmail
    |--------------------------------------------------------------------------
    */

    'webmail_url'         => env('OPANEL_WEBMAIL_URL', 'http://SERVER_IP:8090'),

    // Shared secret for one-click SSO into the OPanel Mail webmail.
    // Must match PANEL_SSO_SECRET in the webmail's .env.
    // Leave empty to disable SSO (webmail link will open the login page instead).
    'webmail_sso_secret'  => env('OPANEL_WEBMAIL_SSO_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | phpMyAdmin
    |--------------------------------------------------------------------------
    */

    // The installer sets up phpMyAdmin on plain HTTP at port 8081. If you put
    // it behind SSL, override via OPANEL_PHPMYADMIN_URL=https://your-host in .env
    'phpmyadmin_url' => env('OPANEL_PHPMYADMIN_URL', 'http://SERVER_IP:8081'),

    // Shared secret for one-click SSO into phpMyAdmin. Must match the value in
    // /etc/opanel/pma-signon-secret on each managed server.
    // Leave empty to disable SSO (clicking phpMyAdmin opens the login page instead).
    'phpmyadmin_sso_secret' => env('OPANEL_PMA_SSO_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'rate_limit' => env('OPANEL_API_RATE_LIMIT', 60),
    ],

    // Shared secret for SSO from github.com/siyamex/panel client zone.
    // Must match OPANEL_SSO_SECRET on github.com/siyamex/panel.
    'sso_secret' => env('OPANEL_SSO_SECRET'),

    'version' => '2.9.16',
    'license_key' => env('OPANEL_LICENSE_KEY', ''),
    'license_server_url' => env('OPANEL_LICENSE_URL', 'https://github.com/siyamex/panel'),

];
