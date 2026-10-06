<?php

namespace App\Jobs;

use App\Models\FerryCertificate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiringCertifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to check for expiring vessel certificates.
     */
    public function handle(): void
    {
        Log::info('CheckExpiringCertifications: Inspecting vessel certificate expiry thresholds.');

        $expiringCount = 0;
        $details = [];

        // Check FerryCertificates expiring within next 60 days
        $expiringCerts = FerryCertificate::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(60)])
            ->with('ferry')
            ->get();

        foreach ($expiringCerts as $cert) {
            $expiringCount++;
            $vesselName = $cert->ferry?->name ?? 'Unknown Vessel';
            $details[] = sprintf(
                'Certificate #%s for %s (Expires: %s, Issued by: %s)',
                $cert->certificate_number,
                $vesselName,
                $cert->expires_at?->format('Y-m-d'),
                $cert->issued_by ?? 'N/A'
            );
        }

        // Dispatch system notifications to Ops Managers & Admins if any expirations found
        if ($expiringCount > 0) {
            $adminIds = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'ops_manager', 'super_admin']);
            })->pluck('id');

            foreach ($adminIds as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'type'    => 'compliance_alert',
                    'channel' => 'system',
                    'title'   => '🔔 Vessel Certificate Expiry Warning',
                    'body'    => sprintf('Found %d vessel certificates expiring within 60 days requiring renewal.', $expiringCount),
                    'data'    => [
                        'expiring_count' => $expiringCount,
                        'items'          => array_slice($details, 0, 10),
                        'checked_at'     => now()->toIso8601String(),
                    ],
                ]);
            }
        }

        Log::info(sprintf('CheckExpiringCertifications: Finished check. Found %d expiring certificates.', $expiringCount));
    }
}
