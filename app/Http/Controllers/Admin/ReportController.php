<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permission;
use App\Services\ReportService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends \App\Foundation\Controller
{
    /**
     * Constructor for ReportController
     */
    public function __construct(
        protected ReportService $service,
        protected ResponseService $responseService
    ) {}

    /**
     * Permission middleware for resource controller methods
     */
    public static function middleware(): array
    {
        return [
            permit(Permission::REPORT_USER_SCAM_STATUS, only: ['userCaseStatusReport']),
            permit(Permission::REPORT_SCAM_STATUS_TRANSITION, only: ['scamStatusTransitionReport']),
        ];
    }

    public function userCaseStatusReport(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {

            return $this->responseService->json(
                success: true,
                data: $this->service->userCaseReport($request)
            );

        }

        return view('admin.reports.user-case-report');
    }

    public function scamStatusTransitionReport(Request $request): View
    {
        $salesStatuses = \App\Models\ScamStatus::where('type', 'sales')->orderBy('title')->get();
        $draftingStatuses = \App\Models\ScamStatus::where('type', 'drafting')->orderBy('title')->get();
        
        $user = auth()->user();
        $query = \App\Models\User::query();

        if ($user && $user->hasAnyRole(['Sales Executive', 'Drafting Executive']) && !$user->hasAnyRole(['Admin', 'Super Admin', 'Manager', 'Sub Admin'])) {
            $query->where('id', $user->id);
        } else {
            $query->whereHas('roles', function($q) {
                $q->whereIn('name', ['Admin', 'Super Admin', 'Sales Executive', 'Drafting Executive', 'Manager', 'Sub Admin']);
            });
        }

        $users = $query->orderBy('name')->get();

        return view('admin.reports.scam-status-transition-report', compact('salesStatuses', 'draftingStatuses', 'users'));
    }
}
