<?php

namespace App\Actions\Scams;

use App\Constants\Permission;
use App\Enums\ScamStatusType;
use App\Http\Requests\Admin\ChangeScamStatusRequest;
use App\Models\Scam;
use App\Models\ScamRegistrationAmount;
use App\Models\ScamStatus;
use App\Models\ScamStatusFile;
use App\Services\ScamService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChangeStatus
{
    public function __construct(
        protected ScamService $scamService
    ) {}

    public function handle(Scam $scam, ChangeScamStatusRequest $request): bool
    {
        $createdRegistration = null;

        $result = DB::transaction(function () use ($scam, $request, &$createdRegistration) {
            $user = $request->user();
            $data = $request->validated();

            $type     = $data['type'];
            $statusId = (int) $data['status_id'];

            /* ================= CURRENT STATUS ================= */
            $currentStatus = $scam->getStatus(
                ScamStatusType::from($type),
                ['id', 'is_lock']
            );

            /* ================= LOCK CHECK ================= */
            if (
                $currentStatus &&
                $currentStatus->is_lock &&
                $user->cannot(
                    $type === ScamStatusType::SALES
                        ? Permission::UPDATE_LOCKED_SALES_STATUS
                        : Permission::UPDATE_LOCKED_DRAFTING_STATUS
                )
            ) {
                return false;
            }

            /* ================= PERMISSION CHECK ================= */
            $p1 = constant(Permission::class . '::' . strtoupper($type) . '_MANAGEMENT');
            $p2 = constant(Permission::class . '::' . strtoupper($type) . '_MANAGEMENT_SELF');

            $assigneeId = $scam->{"{$type}_assignee_id"};

            if (
                !$user->can($p1->value) &&
                !($user->can($p2->value) && $assigneeId == $user->id)
            ) {
                return false;
            }

            /* ================= FIRST TIME DATA ================= */
            $isDraftingPending = $type === ScamStatusType::DRAFTING->value && is_null($scam->drafting_status_id);
            $isSalesPending    = $type === ScamStatusType::SALES->value && is_null($scam->sales_status_id);

            if ($isDraftingPending || $isSalesPending) {
                $scam->fill($request->only('scam_amount', 'scam_type_id'));
                $scam->customer()->update(
                    $request->only('first_name', 'last_name', 'email')
                );
            }

            /* ================= UPDATE STATUS ================= */
            $scam->fill([
                "{$type}_status_id" => $statusId
            ]);

            if ($scam->isDirty()) {

                if ($files = $request->input('files')) {
                    $batchId = ScamStatusFile::generateBatchId($scam);

                    $scam->scamStatusFiles()->createMany(
                        array_map(fn ($file) => [
                            'scam_id'   => $scam->id,
                            'status_id' => $statusId,
                            'file_id'   => $file,
                            'batch_id'  => $batchId,
                        ], $files)
                    );
                }

                $this->scamService->logScamActivityBeforeUpdate($scam);
                $scam->update();
            }

            /* ================= REGISTERED LOGIC (USING SLUG) ================= */
            log::info('Checking for REGISTERED status logic', [
                'type' => $type,
                'status_id' => $statusId,
                'scam_id' => $scam->id,
            ]);

            $status = ScamStatus::find($statusId);
            $isRegistered = $status?->isRegistered();
            $alreadyRegistered = $scam->registrations()->exists();
            $registrationAmountId =
                $request->input('scam_registration_amount_id')
                ?? ScamRegistrationAmount::where('is_active', 1)->value('id')
                ?? ScamRegistrationAmount::query()->value('id');

            Log::info('REGISTERED status debug', [
                'status_id' => $statusId,
                'status_slug' => $status?->slug,
                'isRegistered' => $isRegistered,
                'alreadyRegistered' => $alreadyRegistered,
                'registrationAmountId' => $registrationAmountId,
                'scam_id' => $scam->id,
            ]);

            if ($isRegistered && !$alreadyRegistered) {
                if ($registrationAmountId) {
                    $createdRegistration = $scam->registrations()->create([
                        'scam_registration_amount_id' => $registrationAmountId,
                        'causer_id' => $user->id,
                        'caused_at' => now(),
                    ]);
                } else {
                    Log::warning('No registration amount found for scam registration', [
                        'scam_id' => $scam->id,
                    ]);
                }
            } else {
                Log::warning('Scam registration not created', [
                    'isRegistered' => $isRegistered,
                    'alreadyRegistered' => $alreadyRegistered,
                    'scam_id' => $scam->id,
                ]);
            }

            log::info('Completed REGISTERED status logic', [
                'created_registration_id' => $createdRegistration->id ?? null,
                'scam_id' => $scam->id,
            ]);

            return true;
        });

        
        /* ================= NOTIFICATION AFTER COMMIT ================= */
        if ($createdRegistration) {
            DB::afterCommit(function () use ($createdRegistration) {
                $activeUsers = \App\Models\User::query()->where('status', 1)->get();
                Log::info('Sending REGISTERED notifications', [
                    'registration_id' => $createdRegistration->id,
                    'active_user_count' => $activeUsers->count(),
                    'active_user_ids' => $activeUsers->pluck('id')->all(),
                ]);
                foreach ($activeUsers as $user) {
                    $alreadyNotified = $user->notifications()
                        ->where('type', \App\Notifications\ScamStatusRegisteredNotification::class)
                        ->where('data->registration_id', $createdRegistration->id)
                        ->exists();
                    if ($alreadyNotified) {
                        Log::info('ScamStatusRegisteredNotification already exists for user', [
                            'user_id' => $user->id,
                            'registration_id' => $createdRegistration->id,
                        ]);
                        continue;
                    }
                    try {
                        $user->notify(new \App\Notifications\ScamStatusRegisteredNotification($createdRegistration));
                        Log::info('ScamStatusRegisteredNotification dispatched', [
                            'user_id' => $user->id,
                            'registration_id' => $createdRegistration->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to notify user for scam registration', [
                            'user_id' => $user->id,
                            'registration_id' => $createdRegistration->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        }

        return $result;
    }
}
