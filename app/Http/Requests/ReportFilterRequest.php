<?php

namespace App\Http\Requests;

use App\Enums\LogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(LogType::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'Jenis mutasi',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal akhir',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ];
    }
}
