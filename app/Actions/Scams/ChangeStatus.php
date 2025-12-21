<?php

namespace App\Actions\Scams;

use App\Constants\Permission;
use App\Enums\ScamStatusType;
use App\Http\Requests\Admin\ChangeScamStatusRequest;
use App\Models\Scam;
use App\Models\ScamStatusFile;
use App\Services\ScamService;
use Illuminate\Support\Facades\DB;

class ChangeStatus
{
    public function __construct(
        protected ScamService $scamService
    ) {}

    public function handle(Scam $scam, ChangeScamStatusRequest $request): bool
    {
        return DB::transaction(function () use ($scam, $request) {
            $user = $request->user();
            $data = $request->validated();

            $type = $data['type'];
            $statusId = $data['status_id'];

            $currentStatus = $scam->getStatus(ScamStatusType::from($type), ['id', 'is_lock']);

            // status lock check
            if (
                $currentStatus
                && $currentStatus->is_lock
                && $user->cannot($type === ScamStatusType::SALES ? Permission::UPDATE_LOCKED_SALES_STATUS : Permission::UPDATE_LOCKED_DRAFTING_STATUS)
            ) {
                return false;
            }

            $p1 = Permission::{strtoupper($type).'_MANAGEMENT'};
            $p2 = Permission::{strtoupper($type).'_MANAGEMENT_SELF'};
            $recordAssigneeId = $scam->{"{$type}_assignee_id"};

            $isDraftingPending = $type === ScamStatusType::DRAFTING->value && is_null($scam->drafting_status_id);
            $isSalesPending = $type === ScamStatusType::SALES->value && is_null($scam->sales_status_id);

            if (
                $user->can($p1->value) ||
                ($user->can($p2->value) && $recordAssigneeId == $user->id)
            ) {
                if ($isDraftingPending || $isSalesPending) {
                    $scam->fill($request->only('scam_amount', 'scam_type_id'));
                    $scam->customer()->update($request->only('first_name', 'last_name', 'email'));
                }

                $scam->fill(["{$type}_status_id" => $statusId ?? null]);

                if ($scam->isDirty()) {

                    if ($files = $request->input('files')) {
                        $batchId = ScamStatusFile::generateBatchId($scam);
                        $scam->scamStatusFiles()->createMany(
                            array_map(fn ($file) => [
                                'scam_id' => $scam->id,
                                'status_id' => $statusId ?? null,
                                'file_id' => $file,
                                'batch_id' => $batchId,
                            ], $files)
                        );
                    }

                    $this->scamService->logScamActivityBeforeUpdate($scam);

                    $scam->update();
                }

                return true;
            }

            return false;
        });
    }
}
