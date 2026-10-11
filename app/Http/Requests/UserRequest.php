<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = $userId !== null;

        return [
            'username' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            // Saat memperbarui, password kosong berarti biarkan yang lama.
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'Username',
            'name' => 'Nama lengkap',
            'email' => 'Email',
            'role' => 'Peran',
            'password' => 'Password',
        ];
    }
}
