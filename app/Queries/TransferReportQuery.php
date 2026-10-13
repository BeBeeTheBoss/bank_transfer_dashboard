<?php

namespace App\Queries;

use App\Models\TransferRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TransferReportQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<TransferRequest>
     */
    public function filtered(array $filters): Builder
    {
        $query = TransferRequest::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $query->where(function (Builder $query) use ($needle): void {
                    $query->where('transaction_no', 'ilike', $needle)
                        ->orWhere('branch', 'ilike', $needle)
                        ->orWhere('requested_telegram_username', 'ilike', $needle)
                        ->orWhere('responded_username', 'ilike', $needle)
                        ->orWhere('remark', 'ilike', $needle)
                        ->orWhereRaw('CAST(requested_telegram_user_id AS TEXT) ILIKE ?', [$needle]);
                });
            })
            ->when($filters['branch'] ?? null, fn (Builder $query, string $branch): Builder => $query->where('branch', $branch))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('requested_at', '>=', CarbonImmutable::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('requested_at', '<=', CarbonImmutable::parse($date)->endOfDay()));

        match ($filters['response_time'] ?? null) {
            'under_5m' => $query->whereNotNull('responded_at')->whereRaw("responded_at - requested_at <= INTERVAL '5 minutes'"),
            'over_5m' => $query->whereNotNull('responded_at')->whereRaw("responded_at - requested_at > INTERVAL '5 minutes'"),
            default => null,
        };

        return $query;
    }

    /**
     * @return Collection<int, string>
     */
    public function branchOptions(): Collection
    {
        return TransferRequest::query()
            ->whereNotNull('branch')
            ->where('branch', '<>', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->values();
    }

    /**
     * @param  Builder<TransferRequest>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TransferRequest>
     */
    public function ordered(Builder $query, array $filters): Builder
    {
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('requested_at'),
            'slowest' => $query->orderByRaw('response_seconds DESC NULLS LAST'),
            'fastest' => $query->orderByRaw('response_seconds ASC NULLS LAST'),
            'amount_high' => $query->orderByDesc('transfer_amount'),
            default => $query->orderByDesc('requested_at'),
        };

        return $query->orderByDesc('id');
    }

    /**
     * @param  Builder<TransferRequest>  $query
     * @return Builder<TransferRequest>
     */
    public function withResponseSeconds(Builder $query): Builder
    {
        $expression = $query->getConnection()->getDriverName() === 'sqlite'
            ? "CASE WHEN responded_at IS NOT NULL THEN CAST(strftime('%s', responded_at) AS INTEGER) - CAST(strftime('%s', requested_at) AS INTEGER) END AS response_seconds"
            : 'CASE WHEN responded_at IS NOT NULL THEN EXTRACT(EPOCH FROM (responded_at - requested_at)) END AS response_seconds';

        return $query->select('*')->selectRaw($expression);
    }
}
