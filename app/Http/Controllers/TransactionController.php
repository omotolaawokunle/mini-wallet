<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use App\Services\TransactionService;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $transactions = Transaction::with('sender', 'receiver')
            ->where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->paginate(20);

        return $this->paginated(
            paginator: $transactions,
            collectionClass: TransactionResource::class
        );
    }

    public function store(TransferRequest $request): JsonResponse
    {
        $this->transactionService->queueTransfer($request->validated());
        return $this->success('Transaction processing');
    }
}
