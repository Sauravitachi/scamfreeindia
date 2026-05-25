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
        if ($admin->cannot(Permission::ADMIN_PANEL->value)) {
            return new LoginPermit(
                canLogin: false,
                message: 'Account is not authorized to visit dashboard.'
            );
        }

        if (!setting(Setting::PANEL_LOGIN, false)) {
            if (!$admin->hasPermissionTo(
                Permission::BYPASS_DISABLED_LOGIN
            )) {

                return new LoginPermit(
                    canLogin:false,
                    message:'Panel login disabled.'
                );
            }
        }

        // STATUS CHECK
        if (!$admin->status) {
            return new LoginPermit(
                canLogin:false,
                message:'Inactive Account!'
            );
        }

        // ROLE BASED IP CHECK
        if ($admin->hasRole(\App\Constants\Role::SUPER_ADMIN->value)) {
            return new LoginPermit(canLogin:true);
        }

        $currentIp = request()->ip();

        foreach ($admin->roles as $role) {

            if (!$role->allowed_ips) {
                continue;
            }

            $allowedIps = explode(',', $role->allowed_ips);

            $allowedIps = array_map(
                'trim',
                $allowedIps
            );

            if (!in_array($currentIp,$allowedIps)) {

                return new LoginPermit(
                    canLogin:false,
                    message:"Login not allowed from this network."
                );
            }
        }

        return new LoginPermit(canLogin:true);
    }
}