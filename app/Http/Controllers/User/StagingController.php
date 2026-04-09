<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Domain;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StagingController extends Controller
{
    /**
     * GET /user/staging — list every site that has (or could have) a staging copy.
     */
    public function index()
    {
        $domains = Domain::with(['account', 'stagingClones'])
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')              // only top-level domains
            ->whereNull('staging_source_id')      // exclude clones themselves
            ->orderBy('domain')
            ->get();

        return view('user.staging.index', compact('domains'));
    }

    /**
     * POST /user/staging/{domain} — create a staging clone.
     */
    public function store(Request $request, Domain $domain)
    {
        $domain->load('account.server', 'account.databases');

        if (! in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        // Refuse if a staging clone already exists.
        if ($domain->stagingClones()->exists()) {
            return back()->with('error', 'A staging environment already exists for this domain. Delete it first to recreate.');
        }

        $stagingDomain = 'staging.' . $domain->domain;
        $username      = $domain->account->username;
        $sourceRoot    = $domain->document_root;
        $stagingRoot   = "/home/{$username}/{$stagingDomain}/public_html";

        // Pick the production database to clone. We use the first DB attached
        // to the account — most sites have only one. Users with multiple DBs
        // can pick after the fact via the file editor on the staging site.
        $sourceDb = $domain->account->databases->first();

        $stagingDbName = null;
        $stagingDbUser = null;
        $stagingDbPass = null;

        if ($sourceDb) {
            $stagingDbName = $this->mysqlSafeName("{$sourceDb->name}_staging");
            $stagingDbUser = $this->mysqlSafeName("{$username}_stg");
            $stagingDbPass = Str::random(20);
        }

        return DB::transaction(function () use ($domain, $stagingDomain, $stagingRoot, $sourceRoot, $sourceDb, $stagingDbName, $stagingDbUser, $stagingDbPass, $username) {

            // 1. Create the subdomain (DB record + agent vhost + filesystem).
            $createResp = AgentService::for($domain->account->server)->post('/domains/create', [
                'domain'           => $stagingDomain,
                'parent_domain'    => $domain->domain,
                'document_root'    => $stagingRoot,
                'username'         => $username,
                'php_version'      => $domain->php_version,
                'htaccess_enabled' => $domain->htaccess_enabled,
            ]);

            if (! $createResp || ! $createResp->successful()) {
                $err = $createResp?->json('error') ?? 'Agent unreachable';
                return back()->with('error', "Failed to create staging subdomain: {$err}");
            }

            $stagingDomainModel = Domain::create([
                'server_id'         => $domain->server_id,
                'account_id'        => $domain->account_id,
                'parent_id'         => $domain->id,
                'staging_source_id' => $domain->id,
                'domain'            => $stagingDomain,
                'document_root'     => $stagingRoot,
                'php_version'       => $domain->php_version,
                'htaccess_enabled'  => $domain->htaccess_enabled,
                'status'            => 'active',
            ]);

            // 2. Create the staging database + user (if there's a production DB to clone).
            if ($sourceDb) {
                $dbResp = AgentService::for($domain->account->server)->post('/databases/create', [
                    'name' => $stagingDbName,
                ]);
                if (! $dbResp || ! $dbResp->successful()) {
                    return back()->with('error', 'Failed to create staging database.');
                }

                $userResp = AgentService::for($domain->account->server)->post('/databases/user-create', [
                    'username' => $stagingDbUser,
                    'password' => $stagingDbPass,
                    'database' => $stagingDbName,
                    'host'     => 'localhost',
                ]);
                if (! $userResp || ! $userResp->successful()) {
                    return back()->with('error', 'Failed to create staging database user.');
                }

                Database::create([
                    'server_id'  => $domain->server_id,
                    'account_id' => $domain->account_id,
                    'name'       => $stagingDbName,
                ]);
                DatabaseUser::create([
                    'server_id'  => $domain->server_id,
                    'account_id' => $domain->account_id,
                    'username'   => $stagingDbUser,
                    'password'   => Hash::make($stagingDbPass), // hashed copy for the panel; agent has the real one
                    'host'       => 'localhost',
                ]);
            }

            // 3. Call the agent to do the file rsync + DB clone + config patches.
            $cloneResp = AgentService::for($domain->account->server)->post('/staging/clone', [
                'username'        => $username,
                'source_root'     => $sourceRoot,
                'staging_root'    => $stagingRoot,
                'source_domain'   => $domain->domain,
                'staging_domain'  => $stagingDomain,
                'source_db'       => $sourceDb?->name,
                'staging_db'      => $stagingDbName,
                'staging_db_user' => $stagingDbUser,
                'staging_db_pass' => $stagingDbPass,
            ]);

            if (! $cloneResp || ! $cloneResp->successful()) {
                $err = $cloneResp?->json('error') ?? 'Agent unreachable';
                return back()->with('error', "Files/DB created but the clone step failed: {$err}");
            }

            ActivityLogger::log('staging.created', 'domain', $stagingDomainModel->id, $stagingDomain,
                "Created staging environment for {$domain->domain}");

            return redirect()->route('user.staging.index')
                ->with('success', "Staging environment created at https://{$stagingDomain}");
        });
    }

    /**
     * DELETE /user/staging/{domain} — delete a staging clone.
     */
    public function destroy(Domain $domain)
    {
        if (! $domain->isStaging()) {
            abort(404);
        }
        if (! in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        $domain->load('account.server');

        // Tell the agent to remove the subdomain (vhost, fpm pool, files).
        AgentService::for($domain->account->server)->post('/domains/delete', [
            'domain'        => $domain->domain,
            'parent_domain' => $domain->stagingSource?->domain,
            'username'      => $domain->account->username,
            'php_version'   => $domain->php_version,
        ]);

        ActivityLogger::log('staging.deleted', 'domain', $domain->id, $domain->domain,
            "Deleted staging environment {$domain->domain}");

        $domain->delete();

        return redirect()->route('user.staging.index')
            ->with('success', 'Staging environment removed.');
    }

    /**
     * Sanitise a name to be MySQL-safe (alphanumeric + underscore, max 64 chars).
     */
    private function mysqlSafeName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        return substr($clean, 0, 64);
    }
}
