<?php

namespace App\View;

use App\Constants\Permission;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as AuthUser;

class ScamTable
{
    private ?string $userType = null;

    private function getUserType(AuthUser|User $user): string
    {
        return $this->userType ??= $user->userType();
    }

    public function getDateHeaderName(AuthUser|User $user): string
    {
        $userType = $this->getUserType($user);

        if ($userType === 'sales') {
            return 'Sales Assigned At';
        } elseif ($userType === 'drafting') {
            return 'Drafting Assigned At';
        }

        return 'Created At';
    }

    public function getDateFieldName(AuthUser|User $user): string
    {
        $userType = $this->getUserType($user);

        if ($userType === 'sales') {
            return 'sales_assigned_at';
        } elseif ($userType === 'drafting') {
            return 'drafting_assigned_at';
        }

        return 'created_at';
    }

    public function getOrderColumnId(AuthUser|User $user): int
    {
        $userType = $this->getUserType($user);

        $colId = 11;

        if ($userType === 'sales') {
            $colId = 8;
        }

        if ($userType === 'drafting') {
            $colId = 6;
        }

        if ($user->can(Permission::SHOW_SCAM_SOURCE)) {
            $colId++;
        }

        return $colId;
    }
}
