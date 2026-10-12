<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->role->canRecordMutation();
    }

    public function rules(): array
    {
        return [
            'logged_date' => ['required', 'date', 'before_or_equal:today'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stok_fisik' => ['required', 'integer', 'min:0', 'max:1000000'],
            'alasan' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'logged_date' => 'Tanggal Opname',
            'product_id' => 'Varian Telur',
            'stok_fisik' => 'Stok Fisik Riil',
            'alasan' => 'Alasan Penyesuaian',
        ];
    }

    public function messages(): array
    {
        return [
            'logged_date.before_or_equal' => 'Tanggal opname tidak boleh di masa depan.',
            'stok_fisik.min' => 'Stok fisik riil tidak boleh bernilai negatif.',
            'alasan.required' => 'Alasan penyesuaian wajib diisi untuk catatan audit opname.',
        ];
    }
}
