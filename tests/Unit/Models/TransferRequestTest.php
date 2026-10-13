<?php

namespace Tests\Unit\Models;

use App\Models\TransferRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransferRequestTest extends TestCase
{
    #[DataProvider('externalAmountValues')]
    public function test_serialization_preserves_external_amount_values_without_decimal_cast_failure(string $amount): void
    {
        $transferRequest = new TransferRequest;
        $transferRequest->setRawAttributes(['transfer_amount' => $amount]);

        $attributes = $transferRequest->toArray();

        $this->assertSame($amount, $attributes['transfer_amount']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function externalAmountValues(): array
    {
        return [
            'empty amount' => [''],
            'formatted amount' => ['1,250.50 MMK'],
        ];
    }
}
