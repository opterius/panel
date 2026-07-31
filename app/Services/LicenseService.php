<?php

namespace App\Services;

/**
 * License enforcement has been removed from this fork.
 *
 * Every method reports a valid, unlimited licence and no request is made to
 * any external licence server. The public API is unchanged so existing call
 * sites (AccountController, ServerController, ProvisioningService,
 * LicenseMiddleware) and the Blade views keep working untouched.
 *
 * Note: 0 means "unlimited" in the original status payload, and the max*()
 * helpers translate that to PHP_INT_MAX — the value the `$atLimit` checks in
 * the controllers and views compare against to hide their limit banners.
 */
class LicenseService
{
    /**
     * The status array shared with every view by LicenseMiddleware.
     */
    public function verify(): array
    {
        return [
            'valid'                => true,
            'reason'               => 'unlicensed_build',
            'message'              => 'Unlimited — licence enforcement removed.',
            'max_domains'          => 0, // 0 = unlimited
            'max_accounts'         => 0,
            'max_servers'          => 0,
            'plan'                 => 'unlimited',
            'subscription_status'  => null,
            'cancel_at_period_end' => false,
            'current_period_end'   => null,
        ];
    }

    /**
     * Kept for the installer's call path; there is no trial to register.
     */
    public function registerTrial(): ?array
    {
        return $this->verify();
    }

    public function isValid(): bool
    {
        return true;
    }

    public function maxDomains(): int
    {
        return PHP_INT_MAX;
    }

    public function maxAccounts(): int
    {
        return PHP_INT_MAX;
    }

    public function maxServers(): int
    {
        return PHP_INT_MAX;
    }

    public function subscriptionStatus(): ?string
    {
        return null;
    }

    public function cancelAtPeriodEnd(): bool
    {
        return false;
    }

    public function currentPeriodEnd(): ?string
    {
        return null;
    }

    public function plan(): string
    {
        return 'unlimited';
    }

    /**
     * No-op — nothing is cached because nothing is fetched.
     */
    public function clearCache(): void
    {
        //
    }
}
