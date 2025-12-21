<?php

namespace App\Enums;

use App\Traits\EnumSupport;

enum ScamAssigneeType: string
{
    use EnumSupport;

    case SALES = 'sales';
    case DRAFTING = 'drafting';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Sales',
            self::DRAFTING => 'Drafting',
            self::SERVICE => 'Service',
        };
    }

    public function statusType(): ?ScamStatusType
    {
        return match ($this) {
            self::SALES => ScamStatusType::SALES,
            self::DRAFTING => ScamStatusType::DRAFTING,
            self::SERVICE => ScamStatusType::SERVICE,
            default => null
        };
    }

    public function assignWhatsappTemplateName(): ?WatiTemplateName
    {
        return match ($this) {
            self::SALES => WatiTemplateName::SALES_CASE_ASSIGN_NOTIFICATION,
            self::DRAFTING => WatiTemplateName::DRAFTING_SCAM_ASSIGN_NOTIFICATION,
            default => null
        };
    }
}
