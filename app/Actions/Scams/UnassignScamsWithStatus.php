<?php

namespace App\Actions\Scams;

use App\Enums\ScamActivityEvent;
use App\Enums\ScamStatusType;
use App\Models\Scam;
use App\Models\ScamStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UnassignScamsWithStatus
{
    protected Carbon $scamsFrom;

    public function __construct()
    {
        $this->scamsFrom = Carbon::parse('2025-05-28');
    }

    public function handle(): void
    {
        DB::transaction(function () {
            foreach ([ScamStatusType::SALES, ScamStatusType::DRAFTING] as $statusType) {
                $this->unassignScams($statusType);
            }
        });
    }

    private function unassignScams(ScamStatusType $statusType): void
    {
        $unassignableStatuses = $this->getUnassignableStatuses($statusType);

        if (empty($unassignableStatuses)) {
            return;
        }

        $scams = $this->getScamsWithStatuses($statusType, $unassignableStatuses->keys());

        foreach ($scams as $scam) {
            $this->tryUnassign($scam, $statusType, $unassignableStatuses);
        }
    }

    private function getUnassignableStatuses(ScamStatusType $statusType)
    {
        return ScamStatus::where('type', $statusType)
            ->where('unassign_scam', true)
            ->where('unassign_scam_in_days', '>', 0)
            ->get(['id', 'unassign_scam_in_days'])
            ->keyBy('id');
    }

    private function getScamsWithStatuses(ScamStatusType $statusType, $statusIds)
    {
        $prefix = $statusType->value;

        return Scam::where('is_duplicate', false)
            ->whereNotNull("{$prefix}_assignee_id")
            ->whereIn("{$prefix}_status_id", $statusIds)
            ->where('created_at', '>=', $this->scamsFrom)
            ->get();
    }

    private function tryUnassign(Scam $scam, ScamStatusType $statusType, $unassignableStatuses): void
    {
        $prefix = $statusType->value;
        $statusId = $scam->{"{$prefix}_status_id"};
        $status = $unassignableStatuses->get($statusId);

        $lastUpdated = $scam->{"{$prefix}_status_updated_at"};

        if (! $status || $lastUpdated > now()->subDays($status->unassign_scam_in_days)) {
            return;
        }

        $originalAssigneeId = $scam->{"{$prefix}_assignee_id"};
        $scam->update(["{$prefix}_assignee_id" => null]);

        $scam->statusUnassignRecords()->create([
            'assignee_id' => $originalAssigneeId,
            'status_id' => $statusId,
            'status_type' => $statusType,
        ]);

        $event = ScamActivityEvent::{strtoupper("{$prefix}_assign")};
        $description = "Removed {$prefix} assignee (Due to status update days limit)";
        $scam->logActivity($description, $event);
    }
}
