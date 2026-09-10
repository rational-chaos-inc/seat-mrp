<?php

namespace RCI\MemberRewards\Listeners;

use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Models\Activity;
use Carbon\Carbon;

class MiningLedgerRecordedListener
{
    public function handle(array $payload): void
    {
        try {
            $characterId = $payload['character_id'] ?? null;
            $date = $payload['date'] ?? null;
            $typeId = $payload['type_id'] ?? null;
            $quantity = $payload['quantity'] ?? 0;
            $totalValue = $payload['total_value'] ?? 0;

            if (!$characterId || !$date || $quantity <= 0) {
                return;
            }

            // Create activity record from mining data
            $sourceId = "mining_ledger_{$characterId}_{$date}_{$typeId}";

            Activity::updateOrCreate(
                [
                    'activity_type' => 'mining',
                    'source_id' => $sourceId,
                ],
                [
                    'character_id' => $characterId,
                    'activity_timestamp' => Carbon::parse($date)->startOfDay(),
                    'corporation_id' => $payload['corporation_id'] ?? null,
                    'metadata' => [
                        'type_id' => $typeId,
                        'quantity' => $quantity,
                        'value' => $totalValue,
                        'ore_type' => $payload['ore_type'] ?? 'Unknown',
                        'ore_category' => $payload['ore_category'] ?? 'ore',
                    ],
                ]
            );

            Log::info("Mining activity recorded from Manager-Core event", [
                'character_id' => $characterId,
                'quantity' => $quantity,
                'value' => $totalValue,
            ]);
        } catch (\Exception $e) {
            Log::error("Error handling mining ledger recorded event", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
