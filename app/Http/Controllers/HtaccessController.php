<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;

class HtaccessController extends Controller
{
    /**
     * Toggle .htaccess support (Nginx → Apache backend) for a domain.
     */
    public function toggle(Request $request, Domain $domain)
    {
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);

        $enable = !$domain->htaccess_enabled;

        $workingDir = dirname($domain->document_root);
        $logsDir = $workingDir . '/logs';

        $response = AgentService::for($domain->account->server)->post('/domains/toggle-htaccess', [
            'domain'           => $domain->domain,
            'document_root'    => $domain->document_root,
            'username'         => $domain->account->username,
            'php_version'      => $domain->php_version ?? '8.3',
            'logs_dir'         => $logsDir,
            'htaccess_enabled' => $enable,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response?->json('error') ?? 'Agent unreachable';
            return back()->with('error', 'Failed to toggle .htaccess support: ' . $error);
        }

        $domain->update(['htaccess_enabled' => $enable]);

        $msg = $enable
            ? '.htaccess support enabled. Apache is now handling PHP for ' . $domain->domain . '.'
            : '.htaccess support disabled. Nginx is now serving PHP directly for ' . $domain->domain . '.';

        return back()->with('success', $msg);
    }
}
