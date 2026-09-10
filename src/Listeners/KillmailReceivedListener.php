<?php

namespace RCI\MemberRewards\Listeners;

use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Models\Activity;

class KillmailReceivedListener
{
    public function handle(array $payload): void
    {
        try {
            $killmailId = $payload['killmail_id'] ?? null;
            $killmailHash = $payload['killmail_hash'] ?? null;
            $characterId = $payload['character_id'] ?? null;
            $timestamp = $payload['timestamp'] ?? null;
            $isKill = $payload['is_kill'] ?? false;
            $iskValue = $payload['value'] ?? 0;

            if (!$killmailId || !$characterId || !$timestamp) {
                return;
            }

            $sourceId = "{$killmailId}_{$killmailHash}";
            $activityType = $isKill ? 'pvp_kill' : 'pvp_loss';

            // Record kill/loss from Manager-Core fast-poll event
            Activity::updateOrCreate(
                [
                    'activity_type' => $activityType,
                    'source_id' => $sourceId,
                ],
                [
                    'character_id' => $characterId,
                    'activity_timestamp' => \Carbon\Carbon::parse($timestamp),
                    'corporation_id' => $payload['corporation_id'] ?? null,
                    'alliance_id' => $payload['alliance_id'] ?? null,
                    'metadata' => [
                        'killmail_id' => $killmailId,
                        'value' => $iskValue,
                        'ship_type_id' => $payload['ship_type_id'] ?? null,
                        'victim_name' => $payload['victim_name'] ?? null,
                        'attacker_count' => $payload['attacker_count'] ?? 0,
                        'system_id' => $payload['system_id'] ?? null,
                    ],
                ]
            );

            Log::info("Killmail recorded from Manager-Core fast-poll", [
                'character_id' => $characterId,
                'is_kill' => $isKill,
                'value' => $iskValue,
                'killmail_id' => $killmailId,
            ]);
        } catch (\Exception $e) {
            Log::error("Error handling killmail received event", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
