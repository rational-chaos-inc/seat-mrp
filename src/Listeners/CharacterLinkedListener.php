<?php

namespace RCI\MemberRewards\Listeners;

use Illuminate\Support\Facades\Log;

class CharacterLinkedListener
{
    public function handle(array $payload): void
    {
        try {
            $characterId = $payload['character_id'] ?? null;
            $userId = $payload['user_id'] ?? null;

            if (!$characterId || !$userId) {
                return;
            }

            Log::info("Character linked to user", [
                'character_id' => $characterId,
                'user_id' => $userId,
            ]);

            // When a new character is linked, it will be picked up by next activity collection
            // No immediate action needed
        } catch (\Exception $e) {
            Log::error("Error handling character linked event", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
