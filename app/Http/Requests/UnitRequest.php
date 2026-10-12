<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id;

        return [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'name')->ignore($unitId),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama satuan',
        ];
    }
}
