<?php

namespace RCI\MemberRewards\Listeners;

use Illuminate\Support\Facades\Log;

class MiningExtractionListener
{
    public function handle(array $payload): void
    {
        try {
            $event = $payload['event'] ?? null; // extraction_ready, extraction_unstable, extraction_expired
            $structureId = $payload['structure_id'] ?? null;
            $corporationId = $payload['corporation_id'] ?? null;
            $extractionTime = $payload['extraction_time'] ?? null;

            if (!$event || !$structureId) {
                return;
            }

            Log::info("Mining extraction event received", [
                'event' => $event,
                'structure_id' => $structureId,
                'corporation_id' => $corporationId,
            ]);

            // Events are informational for tracking mining operations
            // No direct activity recording needed - mining_ledger will have the data
            // This is useful for alerting on extraction readiness, etc.
        } catch (\Exception $e) {
            Log::error("Error handling mining extraction event", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
