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
        
        $users = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Admin', 'Super Admin', 'Sales Executive', 'Drafting Executive', 'Manager', 'Sub Admin']);
        })->orderBy('name')->get();

        return view('admin.reports.scam-status-transition-report', compact('salesStatuses', 'draftingStatuses', 'users'));
    }
}
