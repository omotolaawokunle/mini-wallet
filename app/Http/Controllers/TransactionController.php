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
        $this->authorize('viewAny', Transaction::class);
        $transactions = Transaction::with('sender', 'receiver')
            ->forUser($user->id)
            ->latest()
            ->paginate(20);

        return $this->paginated(
            paginator: $transactions,
            collectionClass: TransactionResource::class
        );
    }

    public function store(TransferRequest $request): JsonResponse
    {
        $this->authorize('transfer', [Transaction::class, $request->sender_id]);
        $this->transactionService->queueTransfer($request->validated());
        return $this->success('Transaction processing');
    }
}
