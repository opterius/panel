<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AgentService
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Create an AgentService instance for a given server.
     */
    public static function for(Server $server): self
    {
        return new self($server);
    }

    /**
     * Ping the agent's health endpoint and update server status.
     *
     * @return array{status: string, os: string, version: string, arch: string, hostname: string, uptime: string}|null
     */
    public function health(): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->get($this->url('/health'));

            if ($response->successful()) {
                $data = $response->json();

                $this->server->update([
                    'status' => 'online',
                    'os' => $data['os'] ?? $this->server->os,
                    'os_version' => $data['version'] ?? $this->server->os_version,
                    'last_ping_at' => now(),
                ]);

                return $data;
            }

            $this->server->update(['status' => 'error']);
            return null;
        } catch (ConnectionException) {
            $this->server->update(['status' => 'offline']);
            return null;
        }
    }

    /**
     * Send an authenticated POST request to the agent.
     */
    public function post(string $path, array $data = []): ?Response
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * Send an authenticated GET request to the agent.
     */
    public function get(string $path): ?Response
    {
        return $this->request('GET', $path);
    }

    /**
     * Send an authenticated DELETE request to the agent.
     */
    public function delete(string $path, array $data = []): ?Response
    {
        return $this->request('DELETE', $path, $data);
    }

    /**
     * Send an authenticated POST with extended timeout (for migrations, backups, etc).
     */
    public function postLong(string $path, array $data = [], int $timeout = 600): ?Response
    {
        return $this->request('POST', $path, $data, $timeout);
    }

    /**
     * Multipart file upload to the agent. Used for cPanel backup imports
     * and any other large file the customer needs to ship to their server.
     *
     * Two important details for large files:
     *
     *  1. The HMAC signature is computed against an empty body. The agent's
     *     `requireAuth` middleware has a special case for /files/upload that
     *     skips body hashing because pre-hashing multi-gigabyte uploads would
     *     require buffering the entire file in memory on both sides.
     *
     *  2. We pass an open *file resource* to attach() instead of reading the
     *     file into memory with file_get_contents(). Guzzle then streams the
     *     file from disk as it sends, keeping memory usage bounded regardless
     *     of file size.
     */
    public function upload(string $path, array $fields, \Illuminate\Http\UploadedFile $file, int $timeout = 1800): ?Response
    {
        $url = $this->url($path);
        $timestamp = now()->toRfc3339String();

        // Sign with empty body — the multipart contents are not part of the HMAC.
        $payload   = $timestamp . 'POST' . $path;
        $signature = hash_hmac('sha256', $payload, $this->server->agent_token);

        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            return null;
        }

        try {
            return Http::withoutVerifying()
                ->timeout($timeout)
                ->withHeaders([
                    'X-Signature' => $signature,
                    'X-Timestamp' => $timestamp,
                ])
                ->attach('file', $stream, $file->getClientOriginalName())
                ->post($url, $fields);
        } catch (ConnectionException) {
            $this->server->update(['status' => 'offline']);
            return null;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Send an HMAC-signed request to the Go agent.
     */
    private function request(string $method, string $path, array $data = [], ?int $timeout = null): ?Response
    {
        $url = $this->url($path);
        $body = !empty($data) ? json_encode($data) : '';
        $timestamp = now()->toRfc3339String();

        // HMAC payload: timestamp + method + path + body. Strip the query
        // string — the agent verifies against r.URL.Path which excludes it.
        $pathOnly = strtok($path, '?');
        $payload = $timestamp . $method . $pathOnly . $body;
        $signature = hash_hmac('sha256', $payload, $this->server->agent_token);

        try {
            $request = Http::withoutVerifying()
                ->timeout($timeout ?? 120)
                ->withHeaders([
                    'X-Signature' => $signature,
                    'X-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ]);

            $response = match ($method) {
                'GET' => $request->get($url),
                'POST' => $request->withBody($body, 'application/json')->post($url),
                'DELETE' => $request->withBody($body, 'application/json')->delete($url),
            };

            return $response;
        } catch (ConnectionException) {
            $this->server->update(['status' => 'offline']);
            return null;
        }
    }

    /**
     * Build the full agent URL for a given path.
     */
    private function url(string $path): string
    {
        $base = rtrim($this->server->agent_url, '/');
        return $base . '/' . ltrim($path, '/');
    }
}
