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

    'default_php_version' => '8.3',

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

    'webmail_url' => env('OPTERIUS_WEBMAIL_URL', 'http://SERVER_IP:8080'),

    'version' => '1.0.0',
    'license_key' => env('OPTERIUS_LICENSE_KEY', ''),
    'license_server_url' => env('OPTERIUS_LICENSE_URL', 'https://opterius.com'),

];
