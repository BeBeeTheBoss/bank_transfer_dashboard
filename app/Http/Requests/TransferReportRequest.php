<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransferReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,checking,phone-confirm,success,failed'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'response_time' => ['nullable', 'in:under_5m,over_5m'],
            'sort' => ['nullable', 'in:newest,oldest,slowest,fastest,amount_high'],
            'per_page' => ['nullable', 'integer', 'in:15,25,50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
