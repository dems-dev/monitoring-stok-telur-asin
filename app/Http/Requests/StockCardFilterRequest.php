<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockCardFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'exists:products,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'Varian Produk',
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
