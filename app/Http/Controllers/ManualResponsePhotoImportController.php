<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualResponsePhotoImportRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ManualResponsePhotoImportController extends Controller
{
    public function __invoke(ManualResponsePhotoImportRequest $request): JsonResponse
    {
        $url = config('services.manual_response_api.url');

        if (blank($url)) {
            return response()->json([
                'message' => __('reports.manual_response.not_configured'),
            ], 503);
        }

        $apiRequest = Http::acceptJson()->timeout(120);
        $apiKey = config('services.manual_response_api.key');

        if (filled($apiKey)) {
            $apiRequest = $apiRequest->withHeaders([
                'bank-transfer-api-key' => $apiKey,
            ]);
        }

        /** @var UploadedFile $photo */
        $photo = $request->file('photo');
        $apiRequest = $this->attachPhoto($apiRequest, $photo);

        $apiPayload = [
            'username' => $request->user()->name,
            'timestamp' => (string) now()->timestamp,
            'transaction_no' => $request->string('transaction_no')->toString(),
        ];

        try {
            $apiResponse = $apiRequest->post($url, $apiPayload);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json([
                'message' => __('reports.manual_response.connection_error'),
            ], 502);
        }

        $payload = $apiResponse->json();
        $payloadStatus = is_array($payload) && is_numeric($payload['status'] ?? null)
            ? (int) $payload['status']
            : null;

        if ($apiResponse->failed() || ($payloadStatus !== null && $payloadStatus >= 400)) {
            $errorStatus = $apiResponse->failed() ? $apiResponse->status() : $payloadStatus;

            return response()->json([
                ...(is_array($payload) ? $payload : []),
                'message' => $apiResponse->json('message') ?? __('reports.manual_response.failed'),
            ], $errorStatus >= 400 && $errorStatus <= 599 ? $errorStatus : 502);
        }

        return response()->json([
            ...(is_array($payload) ? $payload : []),
            'message' => $apiResponse->json('message') ?? __('reports.manual_response.success'),
        ]);
    }

    private function attachPhoto(PendingRequest $request, UploadedFile $photo): PendingRequest
    {
        return $request->attach(
            config('services.manual_response_api.field', 'image'),
            $photo->getContent(),
            $photo->getClientOriginalName(),
        );
    }
}
