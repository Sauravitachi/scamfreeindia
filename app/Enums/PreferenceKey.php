<?php

namespace App\Enums;

use App\Traits\EnumSupport;

enum PreferenceKey: string
{
    use EnumSupport;

    case THEME = 'theme';
    case MENU_LAYOUT = 'menu_layout';

    public function label(): string
    {
        return match ($this) {
            self::THEME => 'Theme',
            self::MENU_LAYOUT => 'Menu Layout'
        };
    }
}
