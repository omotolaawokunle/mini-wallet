<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Services\ResponseService;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('transfer', [Transaction::class, $this->user()->id]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sender_id' => 'required|exists:users,id|different:receiver_id',
            'receiver_id' => 'required|exists:users,id|different:sender_id',
            'amount' => 'required|numeric|min:1',
            'commission_fee' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sender_id.required' => 'Sender is required',
            'sender_id.exists' => 'Sender is not valid',
            'sender_id.different' => 'Sender and receiver cannot be the same',
            'receiver_id.required' => 'Receiver is required',
            'receiver_id.exists' => 'Receiver is not valid',
            'receiver_id.different' => 'Sender and receiver cannot be the same',
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
        $data = [
            'sender_id' => $this->user()->id,
        ];

        if ($this->has('amount') && is_numeric($this->amount)) {
            $data['commission_fee'] = $this->amount * config('constant.commission_percentage');
        } else {
            $data['commission_fee'] = 0;
        }

        $this->merge($data);
    }

    public function failedAuthorization()
    {
        return ResponseService::error(message: 'Your account has been flagged. Please contact support.', statusCode: 403);
    }
}
