<?php

namespace App\Http\Requests\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceFobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_plan_barging' => ['required', 'integer', 'exists:operation.plan_bargings,id_plan_barging', 'unique:operation.invoice_fob,id_plan_barging'],
            'id_journal'      => ['required', 'integer', 'exists:finance.journal,id_journal'],
            'id_kontak'       => ['required', 'integer', 'exists:mysql.kontak,id_kontak'],
            'date'            => ['required', 'date'],
            'description'     => ['required', 'string'],

            'hpm'             => ['required', 'numeric'],
            'hma'             => ['required', 'numeric'],
            'kurs'            => ['required', 'numeric'],
            'price'           => ['required', 'numeric'],
            'mc'              => ['required', 'numeric'],
            'tonage'          => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_plan_barging.required' => 'ID Plan Barging wajib diisi.',
            'id_plan_barging.integer'  => 'ID Plan Barging harus berupa angka.',
            'id_plan_barging.exists'   => 'ID Plan Barging tidak ditemukan dalam database operation.',
            'id_plan_barging.unique'   => 'ID Plan Barging sudah digunakan pada invoice lain.',

            'id_journal.required' => 'ID Journal wajib diisi.',
            'id_journal.integer'  => 'ID Journal harus berupa angka.',
            'id_journal.exists'   => 'ID Journal tidak ditemukan dalam database finance.',

            'id_kontak.required' => 'ID Kontak wajib diisi.',
            'id_kontak.integer'  => 'ID Kontak harus berupa angka.',
            'id_kontak.exists'   => 'ID Kontak tidak ditemukan dalam database main.',

            'date.required' => 'Tanggal wajib diisi.',
            'date.date'     => 'Format tanggal tidak valid.',

            'description.required' => 'Deskripsi wajib diisi.',
            'description.string'   => 'Deskripsi harus berupa teks.',

            'hpm.required' => 'HPM wajib diisi.',
            'hpm.numeric'  => 'HPM harus berupa angka.',

            'hma.required' => 'HMA wajib diisi.',
            'hma.numeric'  => 'HMA harus berupa angka.',

            'kurs.required' => 'Kurs wajib diisi.',
            'kurs.numeric'  => 'Kurs harus berupa angka.',

            'price.required' => 'Harga wajib diisi.',
            'price.numeric'  => 'Harga harus berupa angka.',

            'mc.required' => 'MC wajib diisi.',
            'mc.numeric'  => 'MC harus berupa angka.',

            'tonage.required' => 'Tonase wajib diisi.',
            'tonage.numeric'  => 'Tonase harus berupa angka.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        ActivityLogHelper::log('validation_error', 0, $errorMessages);

        return ApiResponseClass::throw('Validation errors', 422, $validator->errors());
    }

    public function prepareForValidation()
    {
        $this->merge([
            'hpm'    => normalizeNumber($this->hpm),
            'hma'    => normalizeNumber($this->hma),
            'kurs'   => normalizeNumber($this->kurs),
            'price'  => normalizeNumber($this->price),
            'mc'     => normalizeNumber($this->mc),
            'tonage' => normalizeNumber($this->tonage),
        ]);
    }
}
