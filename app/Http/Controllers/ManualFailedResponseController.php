<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualFailedResponseRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ManualFailedResponseController extends Controller
{
    public function __invoke(ManualFailedResponseRequest $request): JsonResponse
    {
        $url = config('services.manual_failed_response_api.url');

        if (blank($url)) {
            return response()->json([
                'message' => __('reports.manual_failed_response.not_configured'),
            ], 503);
        }

        $apiRequest = Http::acceptJson()->timeout(120);
        $apiKey = config('services.manual_failed_response_api.key');

        if (filled($apiKey)) {
            $apiRequest = $apiRequest->withHeaders([
                'bank-transfer-api-key' => $apiKey,
            ]);
        }

        try {
            $apiResponse = $apiRequest->post($url, [
                'username' => $request->user()->name,
                'remark' => $request->string('remark')->trim()->toString(),
                'transaction_no' => $request->string('transaction_no')->toString(),
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json([
                'message' => __('reports.manual_failed_response.connection_error'),
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
                'message' => $apiResponse->json('message') ?? __('reports.manual_failed_response.failed'),
            ], $errorStatus >= 400 && $errorStatus <= 599 ? $errorStatus : 502);
        }

        return response()->json([
            ...(is_array($payload) ? $payload : []),
            'message' => $apiResponse->json('message') ?? __('reports.manual_failed_response.success'),
        ]);
    }
}
