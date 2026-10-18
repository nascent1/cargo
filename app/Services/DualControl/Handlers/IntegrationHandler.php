<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Services\DualControl\DualControlHandler;
use App\Services\Integrations\IntegrationConfigService;
use Carbon\CarbonInterface;

/**
 * SMS gateway / SMTP server changes (IntegrationConfigService applies them).
 */
class IntegrationHandler implements DualControlHandler
{
    public function __construct(private IntegrationConfigService $integrations)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $this->integrations->applyApproved($request);
    }
}
