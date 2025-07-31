<?php

namespace App\Http\Requests\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransactionTermRequest extends FormRequest
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
            'id_transaction' => ['required', 'integer', 'exists:finance.transaction,id_transaction'],
            'nama'           => ['required', 'string'],
            'date'           => ['required', 'date'],
            'percent'        => ['required', 'integer'],
            'value_ppn'      => ['required', 'integer'],
            'value_pph'      => ['required', 'integer'],
            // 'value_deposit'  => ['required', 'integer'],
            // 'deposit'        => ['required', 'string', 'in:down_payment,advance_payment'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_transaction.required' => 'Transaction ID wajib',
            'id_transaction.integer'  => 'Transaction ID harus berupa integer',
            'id_transaction.exists'   => 'Transaction ID tidak ditemukan',
            'nama.required'           => 'Name wajib',
            'nama.string'             => 'Name harus berupa string',
            'date.required'           => 'Date wajib',
            'date.date'               => 'Date harus berupa date',
            'percent.required'        => 'Percent wajib',
            'percent.integer'         => 'Percent harus berupa integer',
            'value_ppn.required'      => 'Value PPN wajib',
            'value_ppn.integer'       => 'Value PPN harus berupa integer',
            'value_pph.required'      => 'Value PPH wajib',
            'value_pph.integer'       => 'Value PPH harus berupa integer',
            'value_deposit.required'  => 'Value Deposit wajib',
            'value_deposit.integer'   => 'Value Deposit harus berupa integer',
            'deposit.required'        => 'Deposit wajib',
            'deposit.string'          => 'Deposit harus berupa string',
            'deposit.in'              => 'Deposit harus berupa down_payment atau advance_payment',
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
}
