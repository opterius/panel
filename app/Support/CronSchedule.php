<?php

namespace App\Support;

/**
 * Builds and explains cron schedule expressions for the visual builder.
 *
 * Supports the standard 5-field cron format and a small set of common
 * presets that the panel exposes as friendly options.
 */
class CronSchedule
{
    public const PRESETS = [
        'every_minute'      => '* * * * *',
        'every_5_minutes'   => '*/5 * * * *',
        'every_10_minutes'  => '*/10 * * * *',
        'every_15_minutes'  => '*/15 * * * *',
        'every_30_minutes'  => '*/30 * * * *',
        'hourly'            => '0 * * * *',
        'every_2_hours'     => '0 */2 * * *',
        'every_6_hours'     => '0 */6 * * *',
        'every_12_hours'    => '0 */12 * * *',
        'daily_midnight'    => '0 0 * * *',
        'daily_3am'         => '0 3 * * *',
        'weekly_sunday'     => '0 0 * * 0',
        'weekly_monday'     => '0 0 * * 1',
        'monthly_first'     => '0 0 1 * *',
        'monthly_last'      => '0 0 28 * *', // approximation
        'yearly'            => '0 0 1 1 *',
    ];

    /**
     * Translate a preset key into the cron expression.
     */
    public static function fromPreset(string $preset): ?string
    {
        return self::PRESETS[$preset] ?? null;
    }

    /**
     * Build an expression from individual fields.
     * Each field accepts the standard cron syntax (numbers, ranges, lists, asterisks).
     */
    public static function fromFields(string $minute, string $hour, string $day, string $month, string $weekday): string
    {
        return trim(implode(' ', [$minute, $hour, $day, $month, $weekday]));
    }

    /**
     * Validate that an expression has 5 fields and each field uses safe characters.
     * Not a full cron parser — defends against shell injection but doesn't
     * verify the schedule is logically valid (e.g. day=32 would pass).
     */
    public static function isValid(string $expression): bool
    {
        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) {
            return false;
        }
        foreach ($parts as $field) {
            if (! preg_match('/^[0-9*,\/\-]+$/', $field)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate a plain-English description from a cron expression.
     * Returns the raw expression if it doesn't match a known pattern.
     */
    public static function describe(string $expression): string
    {
        $expression = trim($expression);

        // Check exact preset matches first
        $reverse = array_flip(self::PRESETS);
        if (isset($reverse[$expression])) {
            return self::presetLabel($reverse[$expression]);
        }

        $parts = preg_split('/\s+/', $expression);
        if (count($parts) !== 5) {
            return $expression;
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        // Common single-field-defined patterns
        if ($minute === '*' && $hour === '*' && $day === '*' && $month === '*' && $weekday === '*') {
            return 'Every minute';
        }
        if ($hour === '*' && $day === '*' && $month === '*' && $weekday === '*' && preg_match('#^\*/(\d+)$#', $minute, $m)) {
            return "Every {$m[1]} minutes";
        }
        if ($day === '*' && $month === '*' && $weekday === '*' && is_numeric($minute) && preg_match('#^\*/(\d+)$#', $hour, $m)) {
            return "Every {$m[1]} hours";
        }
        if ($day === '*' && $month === '*' && $weekday === '*' && is_numeric($minute) && is_numeric($hour)) {
            return sprintf('Every day at %02d:%02d', (int) $hour, (int) $minute);
        }
        if ($day === '*' && $month === '*' && is_numeric($minute) && is_numeric($hour) && in_array($weekday, ['0','1','2','3','4','5','6'])) {
            $names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            return sprintf('Every %s at %02d:%02d', $names[(int) $weekday], (int) $hour, (int) $minute);
        }
        if ($month === '*' && $weekday === '*' && is_numeric($minute) && is_numeric($hour) && is_numeric($day)) {
            return sprintf('On day %d of every month at %02d:%02d', (int) $day, (int) $hour, (int) $minute);
        }

        return $expression;
    }

    /**
     * Friendly label for a preset key.
     */
    public static function presetLabel(string $preset): string
    {
        return [
            'every_minute'     => 'Every minute',
            'every_5_minutes'  => 'Every 5 minutes',
            'every_10_minutes' => 'Every 10 minutes',
            'every_15_minutes' => 'Every 15 minutes',
            'every_30_minutes' => 'Every 30 minutes',
            'hourly'           => 'Every hour',
            'every_2_hours'    => 'Every 2 hours',
            'every_6_hours'    => 'Every 6 hours',
            'every_12_hours'   => 'Every 12 hours',
            'daily_midnight'   => 'Every day at midnight',
            'daily_3am'        => 'Every day at 03:00',
            'weekly_sunday'    => 'Every Sunday at midnight',
            'weekly_monday'    => 'Every Monday at midnight',
            'monthly_first'    => 'On the 1st of every month',
            'monthly_last'     => 'On the 28th of every month',
            'yearly'           => 'Once a year (Jan 1)',
        ][$preset] ?? $preset;
    }
}
