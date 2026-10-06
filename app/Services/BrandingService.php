<?php

namespace App\Services;

use App\Models\SystemConfig;

/**
 * Branding Resolution Service
 *
 * Resolves platform branding based on system configuration.
 */
class BrandingService
{
    /**
     * Get the current platform branding config.
     */
    public function getPlatformBranding(): array
    {
        return [
            'platform_name' => SystemConfig::getValue('platform_name', 'Ferry Booking Platform'),
            'company_name'  => SystemConfig::getValue('company_name', 'Ferry Booking Platform'),
            'support_email' => SystemConfig::getValue('support_email', 'support@ferrybook.co.tz'),
            'support_phone' => SystemConfig::getValue('support_phone', '+255 000 000 000'),
        ];
    }
}
