<?php

namespace App\Http\Requests;

use App\Enums\LogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    /** Jenis mutasi diturunkan dari rute, bukan dari input pengguna. */
    public function movementType(): LogType
    {
        return $this->routeIs('stock.keluar.*') ? LogType::KELUAR : LogType::MASUK;
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->role->canRecordMutation();
    }

    /**
     * Mutasi masuk tidak punya pilihan status di form; prototipe selalu
     * menyimpannya sebagai "Selesai", jadi isi di sini daripada menitipkan
     * nilai itu ke input tersembunyi yang bisa diubah klien.
     */
    protected function prepareForValidation(): void
    {
        if ($this->movementType() === LogType::MASUK) {
            $this->merge(['status' => 'Selesai']);
        }
    }

    public function rules(): array
    {
        return [
            'logged_date' => ['required', 'date', 'before_or_equal:today'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'source_or_target' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->movementType()->allowedStatuses())],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'logged_date' => 'Tanggal',
            'product_id' => 'Varian telur',
            'amount' => 'Jumlah',
            'source_or_target' => $this->movementType() === LogType::KELUAR ? 'Tujuan' : 'Sumber',
            'status' => 'Status',
            'note' => 'Catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'logged_date.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'amount.min' => 'Jumlah minimal 1 butir.',
        ];
    }
}
