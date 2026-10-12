<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => [
                'required', 'string', 'max:50',
                Rule::unique('products', 'sku')->ignore($product?->id)->withoutTrashed(),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => [
                'required', 'integer', 'exists:units,id',
                // Angka stok & riwayat mutasi bermakna dalam satuan lama; mengganti
                // satuan (mis. butir -> pack) akan mengubah artinya tanpa jejak audit.
                function (string $attribute, mixed $value, \Closure $fail) use ($product): void {
                    if ($product && (int) $value !== $product->unit_id && $product->hasMovements()) {
                        $fail('Satuan tidak dapat diubah karena produk sudah memiliki riwayat mutasi stok. Buat produk baru untuk satuan yang berbeda.');
                    }
                },
            ],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'purchase_price' => ['required', 'integer', 'min:1', 'max:100000000'],
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
            'purchase_price.required' => 'Harga beli (modal) wajib diisi karena dipakai untuk menghitung nilai persediaan.',
            'purchase_price.min' => 'Harga beli minimal Rp 1.',
            'min_stock.min' => 'Batas minimum stok tidak boleh negatif.',
        ];
    }
}
