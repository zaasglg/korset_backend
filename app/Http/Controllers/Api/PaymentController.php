<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FreedomPayService;
use App\Services\WalletService;
use App\Models\PaymentSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private FreedomPayService $freedomPayService,
        private WalletService $walletService
    ) {}

    /**
     * Создать платежную сессию для пополнения
     */
    public function createTopUpSession(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $amount = $request->input('amount');
        $description = $request->input('description', 'Пополнение баланса');

        try {
            $paymentSession = $this->freedomPayService->createPaymentSession($user, $amount, $description);
            $paymentData = $this->freedomPayService->initPayment($paymentSession);

            return response()->json([
                'success' => true,
                'message' => 'Платежная сессия создана',
                'data' => [
                    'session_id' => $paymentSession->id,
                    'order_id' => $paymentSession->order_id,
                    'amount' => $paymentSession->amount,
                    'currency' => $paymentSession->currency,
                    'payment_url' => $paymentData['pg_redirect_url'] ?? null,
                    'payment_id' => $paymentData['pg_payment_id'] ?? null,
                    'status' => $paymentData['pg_status'] ?? null,
                    'expires_at' => $paymentSession->expires_at,
                    'provider_data' => $paymentData,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment session creation failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания платежной сессии: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статус платежной сессии
     */
    public function getSessionStatus(Request $request, int $sessionId): JsonResponse
    {
        $user = $request->user();
        
        $paymentSession = PaymentSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$paymentSession) {
            return response()->json([
                'success' => false,
                'message' => 'Платежная сессия не найдена',
            ], 404);
        }

        try {
            // Проверяем статус в FreedomPay если сессия еще pending
            if ($paymentSession->status === 'pending' && !$paymentSession->isExpired()) {
                $statusData = $this->freedomPayService->checkPaymentStatus($paymentSession);
                
                // Обновляем статус если платеж завершен
                if (isset($statusData['pg_result']) && ($statusData['pg_result'] === '1' || $statusData['pg_result'] === 1)) {
                    $paymentSession->markAsPaid();
                    
                    // Пополняем баланс
                    $this->walletService->deposit(
                        $user,
                        $paymentSession->amount,
                        'Пополнение через FreedomPay',
                        $paymentSession->order_id
                    );
                }
            }

            // Проверяем истечение срока
            if ($paymentSession->status === 'pending' && $paymentSession->isExpired()) {
                $paymentSession->update(['status' => 'expired']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $paymentSession->id,
                    'order_id' => $paymentSession->order_id,
                    'status' => $paymentSession->status,
                    'amount' => $paymentSession->amount,
                    'currency' => $paymentSession->currency,
                    'created_at' => $paymentSession->created_at,
                    'expires_at' => $paymentSession->expires_at,
                    'paid_at' => $paymentSession->paid_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка проверки статуса платежа',
            ], 500);
        }
    }

    /**
     * Получить список платежных сессий пользователя
     */
    public function getUserSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min($request->get('limit', 20), 100);
        $offset = $request->get('offset', 0);

        $sessions = $user->paymentSessions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $sessions->count() === $limit,
            ],
        ]);
    }

    /**
     * Callback от FreedomPay
     */
    public function freedomPayCallback(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('FreedomPay callback received', [
                'data' => $data,
                'raw_content' => $request->getContent(),
                'headers' => $request->headers->all(),
            ]);

            $result = $this->freedomPayService->handleCallback($data);

            if ($result) {
                // FreedomPay ожидает XML ответ
                return response('<?xml version="1.0" encoding="utf-8"?><response><pg_status>ok</pg_status></response>')
                    ->header('Content-Type', 'application/xml');
            } else {
                return response('<?xml version="1.0" encoding="utf-8"?><response><pg_status>error</pg_status></response>', 400)
                    ->header('Content-Type', 'application/xml');
            }

        } catch (\Exception $e) {
            Log::error('FreedomPay callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'raw_content' => $request->getContent(),
            ]);

            return response('<?xml version="1.0" encoding="utf-8"?><response><pg_status>error</pg_status></response>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}