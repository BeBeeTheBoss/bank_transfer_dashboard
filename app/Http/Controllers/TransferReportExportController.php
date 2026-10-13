<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferReportRequest;
use App\Models\TransferRequest;
use App\Queries\TransferReportQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferReportExportController extends Controller
{
    public function __invoke(TransferReportRequest $request, TransferReportQuery $reportQuery): StreamedResponse
    {
        $filters = $request->validated();
        $query = $reportQuery->withResponseSeconds($reportQuery->filtered($filters));

        $reportQuery->ordered($query, $filters);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            $this->writeCsvRow($output, array_values(__('reports.export.columns')));

            foreach ($query->cursor() as $record) {
                $this->writeCsvRow($output, $this->recordRow($record));
            }

            fclose($output);
        }, 'transfer-records-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @return list<string|int|float|null>
     */
    private function recordRow(TransferRequest $record): array
    {
        return [
            $record->transaction_no,
            $record->branch,
            $record->requested_telegram_username,
            $record->requested_telegram_user_id,
            $record->getRawOriginal('requested_at'),
            $record->getRawOriginal('responded_at'),
            $record->response_seconds === null ? null : (int) round((float) $record->response_seconds),
            $record->transfer_amount,
            $this->translatedStatus($record->status),
            $record->responded_username,
            $record->remark,
            $record->requested_screenshot,
            $record->responded_screenshot,
        ];
    }

    private function translatedStatus(?string $status): string
    {
        $translationKey = match ($status) {
            'phone-confirm' => 'phone_confirm',
            'pending', 'checking', 'success', 'complete', 'failed' => $status,
            default => null,
        };

        return $translationKey === null ? (string) $status : __("ui.status.{$translationKey}");
    }

    /**
     * @param  resource  $output
     * @param  array<array-key, mixed>  $row
     */
    private function writeCsvRow($output, array $row): void
    {
        $safeRow = array_map(function (mixed $value): string {
            $text = $value === null ? '' : (string) $value;

            return preg_match('/^[\s]*[=+\-@]/u', $text) === 1 ? "'{$text}" : $text;
        }, $row);

        fputcsv($output, $safeRow, ',', '"', '');
    }
}
