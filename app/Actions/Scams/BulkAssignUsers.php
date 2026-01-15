<?php

namespace App\Actions\Scams;

use App\Constants\Permission;
use App\Http\Requests\Admin\BulkAssignUserToScamRequest;
use App\Models\CustomerEnquiry;
use App\Models\Scam;
use App\Notifications\CaseAssignedNotification;
use App\Services\ScamService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BulkAssignUsers
{
    public function __construct(
        protected ScamService $scamService
    ) {}

    public function handle(BulkAssignUserToScamRequest $request): bool
    {
        return DB::transaction(function () use ($request): bool {

            $user = $request->user();
            $data = $request->validated();

            $types = ['sales', 'drafting', 'service'];

            $update = [];

            $scams = Scam::with(['salesStatus:id,is_lock', 'draftingStatus:id,is_lock'])->whereIn('id', $data['scams'])->get();
            $customerEnquiries = isset($data['customer_enquiries']) && ! empty($data['customer_enquiries']) ? CustomerEnquiry::whereIn('id', $data['customer_enquiries'])->get(['id']) : null;

            foreach ($types as $type) {
                $permissionConst = strtoupper($type) . '_MANAGEMENT';
                $permission = constant(Permission::class . '::' . $permissionConst);

                if ($user->can($permission->value)) {
                    if (($data["{$type}_assignee_id"] ?? null) !== null) {
                        $update["{$type}_assignee_id"] = ($data["{$type}_assignee_id"] == 0) ? null : $data["{$type}_assignee_id"];
                    }

                    if ($type != 'service') {
                        if (($data["{$type}_status_id"] ?? null) !== null) {
                            $update["{$type}_status_id"] = ($data["{$type}_status_id"] == 0) ? null : $data["{$type}_status_id"];
                        }
                    }
                }
            }

            if (! empty($update)) {
                $scams->each(function (Scam $scam, int $index) use ($user, $update, $customerEnquiries): void {

                    // locked status permission check
                    if ($scam->salesStatus?->is_lock && $user->cannot(Permission::UPDATE_LOCKED_SALES_STATUS)) {
                        unset($update['sales_status_id']);
                    }
                    if ($scam->draftingStatus?->is_lock && $user->cannot(Permission::UPDATE_LOCKED_DRAFTING_STATUS)) {
                        unset($update['drafting_status_id']);
                    }

                    $scam->fill($update);

                    if (
                        $customerEnquiries?->isNotEmpty() &&
                        isset($customerEnquiries[$index]) &&
                        (
                            $scam->isDirty('sales_assignee_id') ||
                            $scam->isDirty('drafting_assignee_id')
                        )
                    ) {
                        $customerEnquiries[$index]->update(['manually_assigned_at' => now()]);
                    }

                    $this->scamService->logScamActivityBeforeUpdate($scam);
                    $scam->save();

                    if ($scam->salesAssignee && $scam->wasChanged('sales_assignee_id') && $scam->sales_assignee_id) {
                        Notification::sendNow($scam->salesAssignee, new CaseAssignedNotification($scam));
                    }
                    if ($scam->draftingAssignee && $scam->wasChanged('drafting_assignee_id') && $scam->drafting_assignee_id) {
                        Notification::sendNow($scam->draftingAssignee, new CaseAssignedNotification($scam));
                    }

                });

                return true;
            }

            return false;
        });
    }
}
