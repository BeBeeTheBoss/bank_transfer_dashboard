<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferRequest extends Model
{
    protected $connection = 'reports';

    public $timestamps = false;

    protected $guarded = ['*'];

    public function getTable(): string
    {
        return (string) config('reporting.transfer_requests_table');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }
}
