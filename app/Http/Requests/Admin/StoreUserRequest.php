<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'emp_code' => ['required', 'string', 'max:50', Rule::unique('users', 'emp_code')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'type' => ['required', Rule::enum(UserType::class)],
        ];
    }
}
