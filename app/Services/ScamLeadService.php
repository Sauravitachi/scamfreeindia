<?php

namespace App\Services;

use App\DTO\ScamLeadTransferValidator;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\BulkDeleteScamLeadRequest;
use App\Http\Requests\Admin\BulkTransferScamLeadRequest;
use App\Http\Requests\Admin\ScamLeadRequest;
use App\Models\Customer;
use App\Models\CustomerEnquiry;
use App\Models\ScamLead;
use App\Models\ScamSource;
use App\Models\ScamType;
use App\Models\User;
use App\Notifications\CustomerEnquiryNotification;
use App\Utilities\Memory;
use App\Utilities\Structure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Rinvex\Country\CountryLoader;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\EloquentDataTable;

class ScamLeadService extends Service
{
    public function dataTable(Request $request): EloquentDataTable
    {
        $query = ScamLead::query();

        $query->with('scamSource:id,slug,title');

        $query->with('existingCustomer:id,track_id,first_name,last_name,phone_number,country_code,dial_code,email');

        $query->leftJoin('scam_types', 'scam_leads.scam_type_id', '=', 'scam_types.id');

        $query->select([
            'scam_leads.*',
            'scam_types.title as scam_type',
        ]);

        $query->where('is_duplicate', false);

        /**
         * Scam Type Filter
         */
        $query->when($request->filled('filter_scam_type_id'), function ($q) use ($request) {
            $q->where('scam_type_id', $request->input('filter_scam_type_id'));
        });

        /**
         * Lead Source Filter
         */
        $query->when($request->filled('filter_scam_source'), function (Builder $q) use ($request) {
            $q->where('scam_source_id', $request->input('filter_scam_source'));
        });

        /**
         * Has Errors Filter
         */
        $query->when($request->filled('filter_has_errors'), function (Builder $q) use ($request) {
            $q->{$request->boolean('filter_has_errors') ? 'whereNotNull' : 'whereNull'}('errors');
        });

        /**
         * Has Already Existing
         */
        $query->when($request->filled('filter_already_exists'), function (Builder $q) use ($request) {
            $q->{$request->boolean('filter_already_exists') ? 'whereNotNull' : 'whereNull'}('existing_customer_id');
        });

        /**
         * Created at range filter
         */
        $query->when($request->filled('filter_created_at'), function (Builder $q) use ($request) {
            $range = carbon_date_range($request->input('filter_created_at'), 'to', expandDates: true);
            $q->whereBetween('scam_leads.created_at', [$range->start, $range->end]);
        });

        $table = datatables()->eloquent($query);

        $table->addColumn('country_name', function (ScamLead $sl): ?string {
            if ($sl->country_code && ($country = country($sl->country_code))) {
                return $country->getEmoji().' '.$country->getName();
            }

            return null;
        });

        $table->editColumn('errors', function (ScamLead $scamLead): array {
            return $scamLead->errors ?? [];
        });

        $table->editColumn('existing_customer', function (ScamLead $scamLead): ?Customer {

            if (! $scamLead->existingCustomer) {
                return null;
            }

            $existingCustomer = $scamLead->existingCustomer;

            $existingCustomer->append(['full_name', 'full_phone_number']);

            $existingCustomer->setAttribute('country_details', Memory::remember(
                "country_emoji_name_{$existingCustomer->country_code}",
                function () use ($existingCustomer) {
                    if ($existingCustomer->country_code) {
                        $country = country($existingCustomer->country_code);

                        return $country->getEmoji().' '.$country->getName();
                    }

                    return null;
                }
            ));

            return $existingCustomer;
        });

        $table->editColumn('phone_number', fn (ScamLead $sl): string => $sl->fullPhoneNumber);
        $table->editColumn('scam_amount', fn (ScamLead $sl): ?string => $sl->scam_amount ? format_amount($sl->scam_amount) : null);
        $table->editColumn('created_at_formatted', fn (ScamLead $sl): string => format_date($sl->created_at));

        return $table;
    }

    public function create(ScamLeadRequest $request): ScamLead
    {
        $scamLead = new ScamLead($request->validated());

        $this->fixCountryCodeForIndia($scamLead);

        $scamLead->save();

        return $scamLead;
    }

    public function update(ScamLead $scamLead, ScamLeadRequest $request): bool
    {
        return DB::transaction(function () use ($request, $scamLead): bool {

            $scamLead->fill($request->validated());
            $scamLead->setAttribute('scam_source_id', $request->validated('scam_source_id', null));

            return $scamLead->save();

        });
    }

    public function delete(ScamLead $scamLead): ScamLead
    {
        return DB::transaction(function () use ($scamLead): ScamLead {
            $scamLead->delete();

            return $scamLead;
        });
    }

    public function transfer(ScamLead $scamLead): bool
    {
        return DB::transaction(function () use ($scamLead): bool {
            // looking for customer with mobile number (if already exists)
            $customer = Customer::where('phone_number', $scamLead->phone_number)->where('country_code', $scamLead->country_code)->first();

            if ($customer) {
                return false;
            }

            $splittedName = $scamLead->name ? Structure::splitFullName($scamLead->name) : null;

            $customer = Customer::create([
                'first_name' => $splittedName?->firstName,
                'last_name' => $splittedName?->lastName,
                'country_code' => $scamLead->country_code,
                'phone_number' => $scamLead->phone_number,
                'email' => $scamLead->email,
            ]);

            $scam = $customer->scams()->create([
                'scam_type_id' => $scamLead->scam_type_id ?? (ScamType::default(['id'])?->id ?? null),
                'scam_amount' => $scamLead->scam_amount,
                'customer_description' => $scamLead->customer_description,
                'source' => $scamLead->source,
                'scam_source_id' => $scamLead->scam_source_id,
            ]);

            if ($scam) {
                $this->delete($scamLead);
            }

            // adding existing customer on rest of leads (if is there any)
            ScamLead::wherePhoneDetails($scamLead->phone_number, $scamLead->country_code ?? 'in')->update([
                'existing_customer_id' => $customer->id,
            ]);

            return true;
        });
    }

    public function sanitizeName(ScamLead $scamLead, bool $save = false): void
    {
        $invalidNames = rescue(callback: fn () => include resource_path(path: 'data/invalid_names.php'), rescue: [], report: false);
        $isNameInvalid = in_array(trim($scamLead->name ?? ''), $invalidNames);

        if ($isNameInvalid) {
            $scamLead->name = null;
        }

        if ($save) {
            $scamLead->save();
        }
    }

    public function setDialCodeFromCountryCode(ScamLead $scamLead, bool $save = false): void
    {
        if (! $scamLead->country_code) {
            return;
        }

        $scamLead->dial_code = CountryLoader::country($scamLead->country_code)?->getCallingCode();

        if ($save) {
            $scamLead->save();
        }
    }

    public function validateTransfer(ScamLead $scamLead): ScamLeadTransferValidator
    {
        $sltErrors = new ScamLeadTransferValidator;

        $phoneNumberLength = strlen(trim($scamLead->phone_number));

        if ($scamLead->country_code === 'in' && $phoneNumberLength !== 10) {
            $sltErrors->push('Invalid Phone Number for India.');
        }

        if ($phoneNumberLength < 9 || $phoneNumberLength > 14) {
            $sltErrors->push('Invalid Phone Number.');
        }

        return $sltErrors;
    }

    public function bulkDelete(BulkDeleteScamLeadRequest $request): ?bool
    {
        $ids = $request->validated('ids');

        return ScamLead::whereIn('id', $ids)->delete();
    }

    public function bulkTransfer(BulkTransferScamLeadRequest $request): bool
    {
        $ids = $request->validated('ids');

        $scamLeads = ScamLead::whereIn('id', $ids)->whereNull('errors')->get();

        if ($scamLeads->count() <= 0) {
            throw new InvalidRequestException('No valid selected leads found for transfer!');
        }

        $scamLeads->each(function (ScamLead $scamLead) {

            $this->transfer($scamLead);

        });

        return true;
    }

    public function fixCountryCodeForIndia(ScamLead $scamLead)
    {
        if (! $scamLead->phone_number) {
            return;
        }

        $len = strlen($scamLead->phone_number);

        if (
            ($len === 12 || $len === 13) &&
            (str_starts_with($scamLead->phone_number, '91') ||
                str_starts_with($scamLead->phone_number, '+91'))
        ) {

            if (str_starts_with($scamLead->phone_number, '+91')) {
                $scamLead->phone_number = substr($scamLead->phone_number, 3); // Remove +91
            } elseif (str_starts_with($scamLead->phone_number, '91')) {
                $scamLead->phone_number = substr($scamLead->phone_number, 2); // Remove 91
            }

            $scamLead->country_code = 'in';
        }
    }

    public function syncExistingCustomerCallback(ScamLead $scamLead): void
    {
        $customer = Customer::where('phone_number', $scamLead->phone_number)
            ->where('country_code', $scamLead->country_code)
            ->first(['id']);

        $scamLead->existing_customer_id = $customer?->id ?? null;

        $scamLead->saveQuietly();
    }

    /**
     * $event - allowed values : 'update', 'delete'
     */
    public function syncIsDuplicateCallback(ScamLead $scamLead, string $event = 'update'): void
    {

        $query = ScamLead::query()
            ->where('phone_number', $scamLead->phone_number)
            ->where('country_code', $scamLead->country_code)
            ->orderBy('created_at', 'ASC')->orderBy('id', 'ASC');

        if ($event === 'delete') {
            $query->whereNot('id', $scamLead->id);
        }

        $leads = $query->get();

        $count = $leads->count();

        if ($count == 1) {

            $leads->first()->updateQuietly(['is_duplicate' => false, 'count' => 1]);

        } elseif ($count > 1) {

            // Get all except the last lead
            ScamLead::whereIn('id', $leads->pluck('id')->slice(0, -1))->update(['is_duplicate' => true, 'count' => 0]);

            // Set the last lead as not duplicate
            $leads->last()->updateQuietly(['is_duplicate' => false, 'count' => $count]);

        }

    }

    public function syncErrorsCallback(ScamLead $lead): void
    {
        $errors = $this->validateTransfer($lead)->getErrors();

        $lead->errors = count($errors) ? $errors : null;

        $lead->saveQuietly();
    }

    public function revalidateAllLeads(): void
    {
        $leads = ScamLead::all();

        $leads->each(function (ScamLead $lead) {
            $this->syncExistingCustomerCallback($lead);
            $this->syncIsDuplicateCallback($lead, event: 'update');
            $this->syncErrorsCallback($lead);
        });

    }

    public function registerLeadFromExternalSource(ScamLeadRequest $request): void
    {
        DB::transaction(function () use ($request): void {

            $scamSource = when(

                condition: $request->has('source'),

                value: function () use ($request): ScamSource {

                    $source = $request->input('source');

                    $slug = Str::slug($source, '_');

                    return ScamSource::where('slug', $slug)->first(['id']) ?? ScamSource::create([
                        'slug' => $slug,
                        'title' => ucwords(str_replace(['_', '-'], ' ', $source)),
                    ]);
                },

                default: fn (): ScamSource => ScamSource::webhookSource(['id'])
            );

            $scamLead = new ScamLead([
                ...$request->validated(),
                'scam_source_id' => $scamSource?->id ?? null,
            ]);

            $this->fixCountryCodeForIndia($scamLead);

            // check for existing customer
            $customer = Customer::wherePhoneDetails($scamLead->phone_number, $scamLead->country_code ?? 'in')->first();

            if ($customer) {

                $enquiry = CustomerEnquiry::createEnquiry($customer, $scamSource);

                $this->sendCustomerEnquiryUserNotification($customer, $enquiry);

            } else {

                $existingLead = ScamLead::wherePhoneDetails($scamLead->phone_number, $scamLead->country_code ?? 'in')
                    ->where('created_at', '>=', now()->subMinutes(1))
                    ->first();

                if ($existingLead) {

                    $existingLead->update($scamLead->getAttributes());

                } else {

                    $scamLead->save();

                }

            }

        });
    }

    public function createCustomerEnquiry(Customer $customer, string $source, ?string $remark = null): void
    {
        $scamSource = ScamSource::firstOrCreate(
            ['slug' => Str::slug($source, '_')],
            ['title' => ucwords(str_replace(['_', '-'], ' ', $source))]
        );

        $enquiry = CustomerEnquiry::createEnquiry($customer, $scamSource, $remark);

        $this->sendCustomerEnquiryUserNotification($customer, $enquiry);
    }

    private function sendCustomerEnquiryUserNotification(Customer $customer, CustomerEnquiry $enquiry): void
    {
        $customer->load([
            'scams' => fn (HasMany $q): HasMany => $q->with([
                'salesAssignee:id',
                'draftingAssignee:id',
                'salesStatus:id,customer_enquiry_notify_role_id,bypass_enquiry',
                'draftingStatus:id,customer_enquiry_notify_role_id,bypass_enquiry',
            ])->latest(),
        ]);

        $notifyTo = collect();

        foreach ($customer->scams as $scam) {

            if (! $scam->draftingAssignee && ! $scam->salesAssignee) {
                continue;
            }

            $role = $scam->draftingAssignee
                ? Role::whereId($scam->draftingStatus?->customer_enquiry_notify_role_id)->first()
                : Role::whereId($scam->salesStatus?->customer_enquiry_notify_role_id)->first();

            if ($role) {
                $roleUserType = userType($role);
            } else {
                $roleUserType = $scam->draftingAssignee ? 'drafting' : 'sales';
            }

            match ($roleUserType) {
                'drafting' => $scam->draftingStatus?->bypass_enquiry ? null : $notifyTo->push($scam->draftingAssignee),
                'sales' => $scam->salesStatus?->bypass_enquiry ? null : $notifyTo->push($scam->salesAssignee),
                default => User::whereHas('roles', fn (Builder $q) => $q->where('id', $role->id))
                    ->get(['id'])
                    ->each(fn ($user) => $notifyTo->push($user))
            };
        }

        Notification::sendNow($notifyTo->unique(), new CustomerEnquiryNotification($enquiry));
    }
}
