<?php

namespace App\Console\Commands;

use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Services\AgentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckAlerts extends Command
{
    protected $signature = 'alerts:check';
    protected $description = 'Check alert rules against current server metrics';

    public function handle(): void
    {
        $rules = AlertRule::where('enabled', true)->with('server')->get();

        foreach ($rules as $rule) {
            try {
                $this->checkRule($rule);
            } catch (\Exception $e) {
                Log::warning("Alert check failed for rule {$rule->id}: " . $e->getMessage());
            }
        }
    }

    private function checkRule(AlertRule $rule): void
    {
        // Get current metrics from agent
        $response = Http::withoutVerifying()
            ->timeout(5)
            ->get($rule->server->agent_url . '/metrics/realtime');

        if (!$response->successful()) return;

        $snapshots = $response->json('snapshots', []);
        if (empty($snapshots)) return;

        // Get the metric value from the latest snapshot
        $latest = end($snapshots);
        $value = match ($rule->metric) {
            'cpu'    => $latest['cpu_percent'] ?? 0,
            'memory' => $latest['mem_percent'] ?? 0,
            'disk'   => $latest['disk_percent'] ?? 0,
            'load'   => $latest['load_avg_1'] ?? 0,
            default  => 0,
        };

        // Check if threshold exceeded
        $exceeded = $rule->operator === '>'
            ? $value > $rule->threshold
            : $value < $rule->threshold;

        if ($exceeded) {
            // Check if already triggered recently (within duration)
            if ($rule->last_triggered_at && $rule->last_triggered_at->diffInMinutes(now()) < $rule->duration_minutes) {
                return; // Don't spam notifications
            }

            // Trigger alert
            $this->triggerAlert($rule, $value);
        }
    }

    private function triggerAlert(AlertRule $rule, float $value): void
    {
        $message = sprintf(
            "ALERT: %s on %s is %s (threshold: %s %s). Current value: %s",
            $rule->metricLabel(),
            $rule->server->name,
            $rule->operator === '>' ? 'too high' : 'too low',
            $rule->operator,
            $rule->threshold,
            round($value, 2)
        );

        $notificationResult = $this->sendNotification($rule, $message);

        // Log the alert
        AlertLog::create([
            'alert_rule_id' => $rule->id,
            'metric'        => $rule->metric,
            'value'         => $value,
            'threshold'     => $rule->threshold,
            'status'        => 'triggered',
            'notification_sent' => $notificationResult,
            'triggered_at'  => now(),
        ]);

        $rule->update(['last_triggered_at' => now()]);

        Log::info("Alert triggered: {$message}");
    }

    private function sendNotification(AlertRule $rule, string $message): string
    {
        $config = $rule->channel_config ?? [];
        $value = $config['value'] ?? '';

        try {
            return match ($rule->channel) {
                'email'    => $this->sendEmail($value, $message),
                'telegram' => $this->sendTelegram($value, $message),
                'slack'    => $this->sendWebhook($value, $message),
                'discord'  => $this->sendDiscord($value, $message),
                default    => 'Unknown channel',
            };
        } catch (\Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function sendEmail(string $to, string $message): string
    {
        \Illuminate\Support\Facades\Mail::raw($message, function ($m) use ($to) {
            $m->to($to)->subject('Opterius Alert');
        });
        return 'Email sent to ' . $to;
    }

    private function sendTelegram(string $config, string $message): string
    {
        // Format: bot_token:chat_id
        $parts = explode(':', $config, 2);
        if (count($parts) < 2) return 'Invalid telegram config (format: BOT_TOKEN:CHAT_ID)';

        $token = $parts[0];
        $chatId = $parts[1];

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $message,
        ]);

        return $response->successful() ? 'Telegram sent' : 'Telegram failed: ' . $response->body();
    }

    private function sendWebhook(string $url, string $message): string
    {
        $response = Http::post($url, [
            'text' => $message,
        ]);

        return $response->successful() ? 'Slack sent' : 'Slack failed: ' . $response->status();
    }

    private function sendDiscord(string $url, string $message): string
    {
        $response = Http::post($url, [
            'content' => $message,
        ]);

        return $response->successful() ? 'Discord sent' : 'Discord failed: ' . $response->status();
    }
}
