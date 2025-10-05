<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ValidateReceiver extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $receiver = User::find($request->receiver_id);
        if (!$receiver) {
            return $this->error(message:'Receiver not found', errors: ['receiver_id' => 'Receiver not found'], statusCode: 422);
        }
        if ($receiver->is_flagged) {
            return $this->error(message: 'Receiver is flagged', errors: ['receiver_id' => 'Receiver cannot receive funds. Please contact support.'], statusCode: 422);
        }
        return $this->success(message: 'Receiver found', data: $receiver);
    }
}
