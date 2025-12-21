<?php

namespace App\Models;

use App\Foundation\Model;
use App\Services\ScamLeadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScamLead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'country_code',
        'dial_code',
        'phone_number',
        'scam_amount',
        'scam_type_id',
        'customer_description',
        'source',
        'is_duplicate',
        'existing_customer_id',
        'scam_source_id',
        'count',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'errors' => 'array',
        'is_duplicate' => 'boolean',
    ];

    /**
     * The "booted" method is called when the model is booted.
     */
    protected static function booted(): void
    {

        static::creating(function (ScamLead $scamLead): void {

            if ($scamLead->country_code === null) {
                $scamLead->country_code = 'in';
            }

            ScamLeadService::getInstance()->sanitizeName($scamLead);
            ScamLeadService::getInstance()->setDialCodeFromCountryCode($scamLead);

        });

        static::saving(function (ScamLead $scamLead): void {

            if ($scamLead->isDirty('country_code')) {
                ScamLeadService::getInstance()->setDialCodeFromCountryCode($scamLead);
            }

        });

        static::saved(function (ScamLead $scamLead): void {

            ScamLeadService::getInstance()->syncIsDuplicateCallback($scamLead, event: 'update');
            ScamLeadService::getInstance()->syncExistingCustomerCallback($scamLead);
            ScamLeadService::getInstance()->syncErrorsCallback($scamLead);

        });

        static::deleted(function (ScamLead $scamLead): void {
            ScamLeadService::getInstance()->syncIsDuplicateCallback($scamLead, event: 'delete');
        });
    }

    public static function scopeWherePhoneDetails(Builder $query, string $phoneNumber, string $countryCode): void
    {
        $query->where('phone_number', $phoneNumber)
            ->where('country_code', $countryCode ?? 'in')->latest();
    }

    public function getFullPhoneNumberAttribute(): string
    {
        if (! $this->hasAllAttributes('phone_number', 'dial_code')) {
            $this->refresh();
        }
        $phoneNumber = $this->phone_number;
        if ($this->dial_code) {
            $phoneNumber = "+$this->dial_code $phoneNumber";
        }

        return $phoneNumber;
    }

    public function getNameAttribute(?string $name): ?string
    {
        if ($name) {

            if (in_array($name, ['{{name}}', '{{full_name}}'])) {
                return null;
            }

            return $name;
        }

        return null;
    }

    public function existingCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'existing_customer_id');
    }

    /**
     * Get the scam source of the scam
     */
    public function scamSource(): BelongsTo
    {
        return $this->belongsTo(ScamSource::class, 'scam_source_id');
    }

    public function scamType(): BelongsTo
    {
        return $this->belongsTo(ScamType::class, 'scam_type_id');
    }
}
