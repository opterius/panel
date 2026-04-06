<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class EmailSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::getGroup('email');

        return view('admin.email-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_default_quota'           => 'required|integer|min:0|max:51200',
            'email_max_send_per_hour'       => 'required|integer|min:0|max:10000',
            'email_max_send_per_day'        => 'required|integer|min:0|max:100000',
            'email_sending_enabled'         => 'boolean',
            'email_receiving_enabled'       => 'boolean',
            'email_max_attachment_mb'        => 'required|integer|min:1|max:100',
            'email_max_accounts_per_domain' => 'required|integer|min:0|max:10000',
        ]);

        Setting::set('email_default_quota', $validated['email_default_quota'], 'email');
        Setting::set('email_max_send_per_hour', $validated['email_max_send_per_hour'], 'email');
        Setting::set('email_max_send_per_day', $validated['email_max_send_per_day'], 'email');
        Setting::set('email_sending_enabled', $request->boolean('email_sending_enabled') ? '1' : '0', 'email');
        Setting::set('email_receiving_enabled', $request->boolean('email_receiving_enabled') ? '1' : '0', 'email');
        Setting::set('email_max_attachment_mb', $validated['email_max_attachment_mb'], 'email');
        Setting::set('email_max_accounts_per_domain', $validated['email_max_accounts_per_domain'], 'email');

        return redirect()->route('admin.email-settings.index')->with('success', __('emails.settings_updated'));
    }
}
