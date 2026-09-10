<?php

namespace RCI\MemberRewards\Listeners;

use Illuminate\Support\Facades\Log;

class CharacterTokenRefreshedListener
{
    public function handle(array $payload): void
    {
        try {
            $characterId = $payload['character_id'] ?? null;

            if (!$characterId) {
                return;
            }

            Log::info("Character token refreshed", [
                'character_id' => $characterId,
            ]);

            // Token has been refreshed, so future ESI calls for this character will work
            // No action needed here - next scheduled collection will pick it up
        } catch (\Exception $e) {
            Log::error("Error handling character token refresh", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
