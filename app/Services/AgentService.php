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
     * Send an HMAC-signed request to the Go agent.
     */
    private function request(string $method, string $path, array $data = []): ?Response
    {
        $url = $this->url($path);
        $body = !empty($data) ? json_encode($data) : '';
        $timestamp = now()->toRfc3339String();

        // HMAC payload: timestamp + method + path + body
        $payload = $timestamp . $method . $path . $body;
        $signature = hash_hmac('sha256', $payload, $this->server->agent_token);

        try {
            $request = Http::withoutVerifying()
                ->timeout(120)
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
