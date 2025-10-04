<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'commission_fee' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sender_id.required' => 'Sender is required',
            'sender_id.exists' => 'Sender is not valid',
            'receiver_id.required' => 'Receiver is required',
            'receiver_id.exists' => 'Receiver is not valid',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be greater than 0',
            'commission_fee.required' => 'Commission fee is required',
            'commission_fee.numeric' => 'Commission fee must be a number',
            'commission_fee.min' => 'Commission fee must be greater than 0',
        ];
    }

    public function attributes(): array
    {
        return [
            'sender_id' => 'Sender',
            'receiver_id' => 'Receiver',
            'amount' => 'Amount',
            'commission_fee' => 'Commission fee',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'commission_fee' => $this->amount * config('constant.commission_percentage'),
        ]);
    }
}
