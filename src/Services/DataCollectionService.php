<?php

namespace RCI\MemberRewards\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Models\Activity;
use Seat\Eveapi\Models\Killmails\Killmail;
use Seat\Eveapi\Models\Wallet\CorporationWalletJournal;
use Seat\Eveapi\Models\Industry\CharacterMining;

class DataCollectionService
{
    public function collectAll(?Carbon $since = null): array
    {
        $results = [
            'mining' => $this->collectMiningData($since),
            'kills' => $this->collectKillData($since),
            'losses' => $this->collectLossData($since),
            'tax' => $this->collectTaxData($since),
        ];

        return $results;
    }

    private function collectMiningData(?Carbon $since = null): int
    {
        $count = 0;

        try {
            if (!$since) {
                $since = Carbon::now()->subDays(90);
            }

            // Query SeAT's character mining ledger
            $miningEntries = CharacterMining::where('date', '>=', $since)
                ->orderBy('date', 'desc')
                ->get();

            foreach ($miningEntries as $entry) {
                $sourceId = "mining_{$entry->character_id}_{$entry->date}_{$entry->type_id}";

                Activity::updateOrCreate(
                    ['source_id' => $sourceId],
                    [
                        'activity_type' => 'mining',
                        'character_id' => $entry->character_id,
                        'activity_timestamp' => Carbon::parse($entry->date)->startOfDay(),
                        'metadata' => [
                            'quantity' => $entry->quantity,
                            'type_id' => $entry->type_id,
                            'type_name' => $entry->type->typeName ?? 'Unknown Ore',
                        ],
                    ]
                );
                $count++;
            }

            Log::info("Collected {$count} mining activities");
        } catch (\Exception $e) {
            Log::error("Error collecting mining data", [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }

    private function collectKillData(?Carbon $since = null): int
    {
        $count = 0;

        try {
            if (!$since) {
                $since = Carbon::now()->subDays(90);
            }

            // Query SeAT's killmails where we got the kill
            $killmails = Killmail::where('killmail_time', '>=', $since)
                ->where('is_loss', false)
                ->orderBy('killmail_time', 'desc')
                ->get();

            foreach ($killmails as $killmail) {
                $attackers = $killmail->attackers ?? collect();

                foreach ($attackers as $attacker) {
                    if (!$attacker['character_id']) {
                        continue;
                    }

                    $sourceId = "kill_{$killmail->killmail_id}_{$attacker['character_id']}";

                    Activity::updateOrCreate(
                        ['source_id' => $sourceId],
                        [
                            'activity_type' => 'pvp_kill',
                            'character_id' => $attacker['character_id'],
                            'activity_timestamp' => $killmail->killmail_time,
                            'metadata' => [
                                'killmail_id' => $killmail->killmail_id,
                                'victim_name' => $killmail->victim['character_name'] ?? 'NPC',
                                'victim_corp' => $killmail->victim['corporation_name'] ?? 'Unknown',
                                'ship_type' => $killmail->victim['ship_type_name'] ?? 'Unknown',
                                'total_value' => $killmail->total_value ?? 0,
                            ],
                        ]
                    );
                    $count++;
                }
            }

            Log::info("Collected {$count} kill activities");
        } catch (\Exception $e) {
            Log::error("Error collecting kill data", [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }

    private function collectLossData(?Carbon $since = null): int
    {
        $count = 0;

        try {
            if (!$since) {
                $since = Carbon::now()->subDays(90);
            }

            // Query SeAT's killmails where we were the victim
            $killmails = Killmail::where('killmail_time', '>=', $since)
                ->where('is_loss', true)
                ->orderBy('killmail_time', 'desc')
                ->get();

            foreach ($killmails as $killmail) {
                if (!$killmail->victim['character_id']) {
                    continue;
                }

                $sourceId = "loss_{$killmail->killmail_id}";

                Activity::updateOrCreate(
                    ['source_id' => $sourceId],
                    [
                        'activity_type' => 'pvp_loss',
                        'character_id' => $killmail->victim['character_id'],
                        'activity_timestamp' => $killmail->killmail_time,
                        'metadata' => [
                            'killmail_id' => $killmail->killmail_id,
                            'final_blow_by' => $killmail->attackers[0]['character_name'] ?? 'Unknown' ?? 'Unknown',
                            'ship_type' => $killmail->victim['ship_type_name'] ?? 'Unknown',
                            'total_value' => $killmail->total_value ?? 0,
                        ],
                    ]
                );
                $count++;
            }

            Log::info("Collected {$count} loss activities");
        } catch (\Exception $e) {
            Log::error("Error collecting loss data", [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }

    private function collectTaxData(?Carbon $since = null): int
    {
        $count = 0;

        try {
            if (!$since) {
                $since = Carbon::now()->subDays(90);
            }

            // Query corporation wallet journals for bounty payouts
            $entries = CorporationWalletJournal::where('ref_type', 'bounty')
                ->where('date', '>=', $since)
                ->orderBy('date', 'desc')
                ->get();

            foreach ($entries as $entry) {
                // owner_id1 is the character who earned the bounty
                if (!$entry->owner_id1) {
                    continue;
                }

                $sourceId = "bounty_{$entry->id}_{$entry->owner_id1}";

                Activity::updateOrCreate(
                    ['source_id' => $sourceId],
                    [
                        'activity_type' => 'tax_wallet',
                        'character_id' => $entry->owner_id1,
                        'corporation_id' => $entry->corporation_id,
                        'activity_timestamp' => $entry->date,
                        'metadata' => [
                            'amount' => abs($entry->amount ?? 0),
                            'tax_amount' => $entry->tax_amount ?? 0,
                            'reason' => $entry->reason ?? 'Bounty Payout',
                            'ref_id' => $entry->ref_id,
                        ],
                    ]
                );
                $count++;
            }

            Log::info("Collected {$count} tax/bounty activities");
        } catch (\Exception $e) {
            Log::error("Error collecting tax data", [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }
}
