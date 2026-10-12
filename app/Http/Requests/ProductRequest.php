<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku' => [
                'required', 'string', 'max:50',
                Rule::unique('products', 'sku')->ignore($productId)->withoutTrashed(),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'purchase_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'nearest_expiry_date' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'Nama produk',
            'category_id' => 'Kategori',
            'unit_id' => 'Satuan',
            'price' => 'Harga Jual',
            'purchase_price' => 'Harga Beli (Modal)',
            'min_stock' => 'Batas minimum',
            'nearest_expiry_date' => 'Tanggal Kedaluwarsa Terdekat',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU ini sudah dipakai produk lain.',
            'price.min' => 'Harga jual tidak boleh negatif.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'min_stock.min' => 'Batas minimum stok tidak boleh negatif.',
        ];
    }
}
