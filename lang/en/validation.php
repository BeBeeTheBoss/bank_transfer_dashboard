<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',
    'enum' => 'The selected :attribute is invalid.',
    'date' => 'The :attribute must be a valid date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'integer' => 'The :attribute must be an integer.',
    'array' => 'The :attribute must be a list.',
    'image' => 'The :attribute must be an image.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'in' => 'The selected :attribute is invalid.',
    'max' => [
        'array' => 'The :attribute must not contain more than :max items.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'min' => [
        'array' => 'The :attribute must contain at least :min item.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'type' => 'user type',
        'branch' => 'branch',
        'status' => 'status',
        'photo' => 'photo',
        'remark' => 'remark',
        'transaction_no' => 'transaction number',
    ],
];
