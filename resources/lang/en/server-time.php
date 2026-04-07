<?php

return [

    // Page header / breadcrumbs
    'page_title'    => 'Server Time',
    'back_to_server'=> 'Back to server',

    // Top status card
    'current_time'    => 'Current server time',
    'timezone_label'  => 'Timezone',
    'last_sync_label' => 'Last NTP sync',
    'never'           => 'Never',
    'unknown'         => 'Unknown',

    // NTP sync status badge
    'ntp_synced'         => 'Clock in sync',
    'ntp_not_synced'     => 'Clock NOT in sync',
    'ntp_offset'         => 'Offset: :ms ms',
    'ntp_offset_unknown' => 'Offset: unknown',
    'ntp_servers'        => 'NTP servers',
    'ntp_no_servers'     => 'No upstream NTP servers reported',

    // Sync now button + result
    'sync_now'      => 'Sync now',
    'sync_now_desc' => 'Force an immediate clock sync against the configured NTP servers.',
    'sync_done'     => 'NTP sync triggered successfully.',
    'sync_failed'   => 'NTP sync failed: :error',

    // Timezone change form
    'change_timezone'      => 'Change timezone',
    'change_timezone_desc' => 'Set the system timezone. This affects log timestamps, scheduled cron jobs, and email headers on this server.',
    'timezone_select'      => 'New timezone',
    'timezone_save'        => 'Update timezone',
    'timezone_updated'     => 'Server timezone changed to :timezone',
    'timezone_failed'      => 'Failed to change timezone: :error',

    // Why this matters explainer (small grey text under the page)
    'why_matters_title' => 'Why this matters',
    'why_matters_text'  => "Cron jobs run on the server's local time. Email headers and SSL certificate validation depend on an accurate clock. If your server's time drifts more than a few minutes, mail starts looking like spam and certificate handshakes start failing.",

    // Agent unreachable
    'agent_unreachable_title' => 'Server agent unreachable',
    'agent_unreachable_text'  => "Could not connect to the Opterius agent on this server. Make sure the agent is running and that port :port is reachable.",

];
