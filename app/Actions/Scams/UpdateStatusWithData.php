<?php

namespace App\Actions\Scams;

use App\Enums\ScamStatusFieldType;
use App\Http\Requests\Admin\UpdateStatusDataRequest;
use App\Models\ScamRegistration;
use App\Models\ScamStatusFile;
use App\Services\ScamService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateStatusWithData
{
    public function handle(UpdateStatusDataRequest $request): void
    {
        DB::transaction(function () use ($request) {
            /**
             * @var \App\Models\Scam $scam
             * @var \App\Models\ScamStatus $scamStatus
             */
            $scam = $request->route('scam');
            $scamStatus = $request->route('scam_status');

            $customerUpdate = [];
            $statusRecordUpdate = [];
            $scamUpdate = [];

            $now = now();

            if (($fieldName = ScamStatusFieldType::FIRST_NAME->name()) && $request->has($fieldName)) {
                $customerUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::LAST_NAME->name()) && $request->has($fieldName)) {
                $customerUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::EMAIL->name()) && $request->has($fieldName)) {
                $customerUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::STATUS_REMARK->name()) && $request->has($fieldName)) {
                $statusRecordUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::STATUS_NOTIFY_AT->name()) && $request->has($fieldName)) {
                $statusRecordUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::SCAM_AMOUNT->name()) && $request->has($fieldName)) {
                $scamUpdate[$fieldName] = $request->validated($fieldName);
            }
            if (($fieldName = ScamStatusFieldType::SCAM_TYPE->name()) && $request->has($fieldName)) {
                $scamUpdate[$fieldName] = $request->validated($fieldName);
            }

            // Updating customer
            $scam->customer()->update($customerUpdate);

            // adding files
            if (($fieldName = ScamStatusFieldType::FILE_UPLOAD->name()) && $request->has($fieldName)) {
                $fileUploads = $request->validated($fieldName, []);
                if (count($fileUploads) > 0) {
                    $batchId = ScamStatusFile::generateBatchId($scam);
                    $scam->scamStatusFiles()->createMany(
                        array_map(fn ($file) => [
                            'scam_id' => $scam->id,
                            'status_id' => $scamStatus->id,
                            'file_id' => $file,
                            'batch_id' => $batchId,
                        ], $fileUploads)
                    );
                }
            }

            if (($fieldName = ScamStatusFieldType::REGISTRATION_AMOUNT->name()) && $request->has($fieldName)) {
                $scamRegistrationAmountIds = $request->validated($fieldName, []);
                $scam->registrations()->delete();
                if (! empty($scamRegistrationAmountIds)) {
                    $data = [];
                    foreach ($scamRegistrationAmountIds as $amountId) {
                        $data[] = [
                            'scam_id' => $scam->id,
                            'scam_registration_amount_id' => $amountId,
                            'causer_id' => Auth::id(),
                            'caused_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    ScamRegistration::insert($data);
                }
            }

            // Updating status
            $scamUpdate["{$scamStatus->type->value}_status_id"] = $scamStatus->id;
            $scam->fill($scamUpdate);
            ScamService::getInstance()->logScamActivityBeforeUpdate($scam);
            $scam->save();

            if (! empty($statusRecordUpdate) && $scam->{"{$scamStatus->type->value}_status_id"}) {
                /**
                 * @var \App\Models\ScamStatusRecord $statusRecord
                 */
                $statusRecord = $scam->{"{$scamStatus->type->value}StatusRecord"};

                if ($statusRecord?->id == $scam->{"{$scamStatus->type->value}_status_record_id"}) {
                    $statusRecord->update($statusRecordUpdate);
                }
            }

        });
    }
}
