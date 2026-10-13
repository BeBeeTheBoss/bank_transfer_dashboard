<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferReportRequest;
use App\Queries\TransferReportQuery;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Inertia\Response;

class TransferReportController extends Controller
{
    public function __invoke(TransferReportRequest $request, TransferReportQuery $reportQuery): Response
    {
        $filters = $request->validated();

        try {
            $base = $reportQuery->filtered($filters);

            $summary = (clone $base)->toBase()
                ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (responded_at - requested_at))) FILTER (WHERE responded_at IS NOT NULL)) AS avg_response_seconds')
                ->selectRaw('COUNT(*) FILTER (WHERE responded_at IS NOT NULL) AS responded_count')
                ->selectRaw("COUNT(*) FILTER (WHERE responded_at IS NOT NULL AND responded_at - requested_at > INTERVAL '5 minutes') AS over_five_minutes_count")
                ->first();

            $query = $reportQuery->withResponseSeconds(clone $base);

            $reportQuery->ordered($query, $filters);

            return Inertia::render('Reports/Index', [
                'records' => $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString(),
                'summary' => $summary,
                'branches' => $reportQuery->branchOptions(),
                'filters' => $filters,
                'databaseError' => null,
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return Inertia::render('Reports/Index', [
                'records' => [
                    'data' => [],
                    'links' => [],
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => (int) ($filters['per_page'] ?? 15),
                ],
                'summary' => ['avg_response_seconds' => null, 'responded_count' => 0, 'over_five_minutes_count' => 0],
                'branches' => [],
                'filters' => $filters,
                'databaseError' => app()->environment('local')
                    ? __('reports.database.local_error')
                    : __('reports.database.error'),
            ]);
        }
    }
}
