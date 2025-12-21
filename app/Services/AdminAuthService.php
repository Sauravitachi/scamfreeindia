<?php

namespace App\Services;

use App\Constants\Permission;
use App\Constants\Setting;
use App\DTO\LoginPermit;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as AuthUser;

class AdminAuthService extends Service
{
    public function canLogin(User|AuthUser $admin): LoginPermit
    {
        // Check 1 : Panel Access Permission
        if ($admin->cannot(Permission::ADMIN_PANEL->value)) {
            return new LoginPermit(canLogin: false, message: 'Account is not authorized to visit the dashboard.');
        }

        // Check 2 : Panel Login Status
        if (! setting(Setting::PANEL_LOGIN, false)) {
            if (! $admin->hasPermissionTo(Permission::BYPASS_DISABLED_LOGIN)) {
                return new LoginPermit(canLogin: false, message: 'Panel login is disabled right now. Try again later!');
            }
        }

        // check of active/inactive status
        if (! $admin->status) {
            return new LoginPermit(canLogin: false, message: 'Inactive Account! Please contact administration.');
        }

        return new LoginPermit(canLogin: true);
    }
}
