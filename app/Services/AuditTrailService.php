<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Audit Trail Service per SRS §5.7 — Hash-chained immutable logging.
 */
class AuditTrailService
{
    public function logCreate(string $entityType, $entityId, array $newState, ?int $userId = null): AuditLog
    {
        return AuditLog::createEntry(
            $userId ?? auth()->id(), 'create', $entityType, $entityId, null, $newState, request()->ip()
        );
    }

    public function logUpdate(string $entityType, $entityId, array $oldState, array $newState, ?int $userId = null): AuditLog
    {
        return AuditLog::createEntry(
            $userId ?? auth()->id(), 'update', $entityType, $entityId, $oldState, $newState, request()->ip()
        );
    }

    public function logDelete(string $entityType, $entityId, array $oldState, ?int $userId = null): AuditLog
    {
        return AuditLog::createEntry(
            $userId ?? auth()->id(), 'delete', $entityType, $entityId, $oldState, null, request()->ip()
        );
    }

    /**
     * Verify the integrity of the audit log hash chain.
     */
    public function verifyChainIntegrity(int $limit = 1000): array
    {
        $entries = AuditLog::orderBy('sequence_number')->limit($limit)->get();
        $errors = [];
        $previousHash = null;

        foreach ($entries as $entry) {
            if ($entry->previous_hash !== $previousHash) {
                $errors[] = [
                    'sequence' => $entry->sequence_number,
                    'expected_previous' => $previousHash,
                    'actual_previous' => $entry->previous_hash,
                ];
            }
            $previousHash = $entry->hash;
        }

        return [
            'valid' => empty($errors),
            'checked' => $entries->count(),
            'errors' => $errors,
        ];
    }
}
