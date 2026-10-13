<?php

return [
    'export' => [
        'columns' => [
            'transaction' => 'Transaction No.',
            'branch' => 'Branch',
            'requester' => 'Requester',
            'requester_id' => 'Requester ID',
            'requested_at' => 'Requested At',
            'responded_at' => 'Responded At',
            'response_seconds' => 'Response Time (seconds)',
            'amount' => 'Amount (MMK)',
            'status' => 'Status',
            'responder' => 'Responder',
            'remark' => 'Remark',
            'requested_screenshot' => 'Requested Screenshot',
            'responded_screenshot' => 'Responded Screenshot',
        ],
    ],
    'database' => [
        'local_error' => 'Could not connect to Cloud PostgreSQL. Check the REPORT_DB_* values and table name in .env.',
        'error' => 'Report data is temporarily unavailable. Please try again.',
    ],
    'photo_import' => [
        'not_configured' => 'The transaction image API has not been configured yet.',
        'connection_error' => 'Could not connect to the transaction image API. Please try again.',
        'failed' => 'The photo could not be processed. Please try again.',
        'success' => 'The photo was sent for processing successfully.',
    ],
    'manual_response' => [
        'not_configured' => 'The manual response API has not been configured yet.',
        'connection_error' => 'Could not connect to the manual response API. Please try again.',
        'failed' => 'The manual response could not be processed. Please try again.',
        'success' => 'The manual response was sent successfully.',
    ],
    'manual_failed_response' => [
        'not_configured' => 'The manual failed-response API has not been configured yet.',
        'connection_error' => 'Could not connect to the manual failed-response API. Please try again.',
        'failed' => 'The failed response could not be processed. Please try again.',
        'success' => 'The transaction was marked as failed successfully.',
    ],
];
