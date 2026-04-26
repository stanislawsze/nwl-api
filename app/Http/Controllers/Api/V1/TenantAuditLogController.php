<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenancy\TenantAuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class TenantAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('viewAuditLogs', $tenant);

        $limit = min(max($request->integer('limit', 50), 1), 100);
        $event = $request->string('event')->toString();

        $logs = Activity::query()
            ->with('causer')
            ->where('log_name', 'tenancy')
            ->where('properties->tenant_id', $tenant->id)
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->latest('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => TenantAuditLogResource::collection($logs),
            'meta' => [
                'limit' => $limit,
            ],
        ]);
    }
}
