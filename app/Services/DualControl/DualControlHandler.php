<?php

namespace App\Services\DualControl;

use App\Models\DualControlRequest;
use Carbon\CarbonInterface;

/**
 * Applies one kind of approved dual-control request. Registered per operation in config/dual_control.php.
 */
interface DualControlHandler
{
    /**
     * Apply the change. Runs inside the approval (or scheduler) transaction.
     *
     * $effectiveAt is when the change takes effect: for "dated" operations the requested start
     * (or now, when that moment has already passed); otherwise now.
     *
     * Throw DualControlException to refuse with a message the checker can read; nothing is applied.
     */
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void;
}
