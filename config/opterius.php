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

    'php_versions' => ['8.2', '8.3', '8.4'],

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

    'agent_port' => env('OPTERIUS_AGENT_PORT', 7443),
    'agent_timeout' => env('OPTERIUS_AGENT_TIMEOUT', 30),

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

    'ns1' => env('OPTERIUS_NS1', 'ns1.opterius.com'),
    'ns2' => env('OPTERIUS_NS2', 'ns2.opterius.com'),

    /*
    |--------------------------------------------------------------------------
    | Webmail
    |--------------------------------------------------------------------------
    */

    'webmail_url'         => env('OPTERIUS_WEBMAIL_URL', 'http://SERVER_IP:8090'),

    // Shared secret for one-click SSO into the Opterius Mail webmail.
    // Must match PANEL_SSO_SECRET in the webmail's .env.
    // Leave empty to disable SSO (webmail link will open the login page instead).
    'webmail_sso_secret'  => env('OPTERIUS_WEBMAIL_SSO_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | phpMyAdmin
    |--------------------------------------------------------------------------
    */

    // The installer sets up phpMyAdmin on plain HTTP at port 8081. If you put
    // it behind SSL, override via OPTERIUS_PHPMYADMIN_URL=https://your-host in .env
    'phpmyadmin_url' => env('OPTERIUS_PHPMYADMIN_URL', 'http://SERVER_IP:8081'),

    // Shared secret for one-click SSO into phpMyAdmin. Must match the value in
    // /etc/opterius/pma-signon-secret on each managed server.
    // Leave empty to disable SSO (clicking phpMyAdmin opens the login page instead).
    'phpmyadmin_sso_secret' => env('OPTERIUS_PMA_SSO_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'rate_limit' => env('OPTERIUS_API_RATE_LIMIT', 60),
    ],

    'version' => '2.2.3',
    'license_key' => env('OPTERIUS_LICENSE_KEY', ''),
    'license_server_url' => env('OPTERIUS_LICENSE_URL', 'https://opterius.com'),

];
