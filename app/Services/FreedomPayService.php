<?php

namespace App\Services;

use App\Models\PaymentSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreedomPayService
{
    private string $merchantId;
    private string $secretKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->merchantId = config('services.freedompay.merchant_id');
        $this->secretKey = config('services.freedompay.secret_key');
        $this->apiUrl = config('services.freedompay.api_url', 'https://api.freedompay.kz');
    }

    /**
     * Создать платежную сессию
     */
    public function createPaymentSession(User $user, float $amount, string $description = 'Пополнение баланса'): PaymentSession
    {
        $orderId = 'WALLET-' . $user->id . '-' . time();

        $paymentSession = PaymentSession::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'KZT',
            'description' => $description,
            'status' => 'pending',
            'payment_provider' => 'freedompay',
            'expires_at' => now()->addHours(1),
        ]);

        return $paymentSession;
    }

    /**
     * Инициализировать платеж в FreedomPay
     */
    public function initPayment(PaymentSession $paymentSession): array
    {
        $salt = $this->generateSalt();

        $params = [
            'pg_order_id' => $paymentSession->order_id,
            'pg_merchant_id' => $this->merchantId,
            'pg_amount' => (string) $paymentSession->amount,
            'pg_description' => $paymentSession->description,
            'pg_salt' => $salt,
            'pg_currency' => $paymentSession->currency,
        ];

        $signature = $this->generateSignature($params, 'init_payment.php');
        $params['pg_sig'] = $signature;

        try {
            // Отправляем данные как URL-encoded строку (как в Postman примере)
            $urlEncodedData = http_build_query($params);

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->withBody($urlEncodedData, 'application/x-www-form-urlencoded')
                ->post($this->apiUrl . '/init_payment.php');

            $responseData = $this->parseXmlResponse($response->body());

            // Сохраняем данные от провайдера
            $paymentSession->update([
                'provider_data' => $responseData,
            ]);

            // Проверяем статус ответа
            if (isset($responseData['pg_status']) && $responseData['pg_status'] === 'error') {
                $errorCode = $responseData['pg_error_code'] ?? 'unknown';
                $errorDescription = $responseData['pg_error_description'] ?? 'Unknown error';

                Log::error('FreedomPay payment initialization error', [
                    'order_id' => $paymentSession->order_id,
                    'error_code' => $errorCode,
                    'error_description' => $errorDescription,
                    'response' => $responseData,
                ]);

                // Обновляем статус сессии
                $paymentSession->update(['status' => 'failed']);

                // Для ошибки 1100 (IP не в whitelist) даем понятное сообщение
                if ($errorCode === '1100') {
                    throw new \Exception('IP-адрес не находится в списке доверенных. Обратитесь к администратору FreedomPay для добавления IP в whitelist.');
                }

                throw new \Exception("Ошибка FreedomPay [{$errorCode}]: {$errorDescription}");
            }

            Log::info('FreedomPay payment initialized successfully', [
                'order_id' => $paymentSession->order_id,
                'payment_id' => $responseData['pg_payment_id'] ?? null,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('FreedomPay payment initialization failed', [
                'order_id' => $paymentSession->order_id,
                'error' => $e->getMessage(),
            ]);

            $paymentSession->update(['status' => 'failed']);

            throw new \Exception('Ошибка инициализации платежа: ' . $e->getMessage());
        }
    }

    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(PaymentSession $paymentSession): array
    {
        $salt = $this->generateSalt();

        $params = [
            'pg_order_id' => $paymentSession->order_id,
            'pg_merchant_id' => $this->merchantId,
            'pg_salt' => $salt,
        ];

        $signature = $this->generateSignature($params, 'get_status.php');
        $params['pg_sig'] = $signature;

        try {
            // Отправляем данные как URL-encoded строку
            $urlEncodedData = http_build_query($params);

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->withBody($urlEncodedData, 'application/x-www-form-urlencoded')
                ->post($this->apiUrl . '/get_status.php');

            return $this->parseXmlResponse($response->body());

        } catch (\Exception $e) {
            Log::error('FreedomPay status check failed', [
                'order_id' => $paymentSession->order_id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Ошибка проверки статуса платежа: ' . $e->getMessage());
        }
    }

    /**
     * Обработать callback от FreedomPay
     */
    public function handleCallback(array $data): bool
    {
        // Проверяем подпись
        if (!$this->verifySignature($data)) {
            Log::warning('Invalid FreedomPay callback signature', $data);
            return false;
        }

        $orderId = $data['pg_order_id'] ?? null;
        if (!$orderId) {
            Log::warning('Missing order_id in FreedomPay callback', $data);
            return false;
        }

        $paymentSession = PaymentSession::where('order_id', $orderId)->first();
        if (!$paymentSession) {
            Log::warning('Payment session not found', ['order_id' => $orderId]);
            return false;
        }

        $status = $data['pg_result'] ?? null;

        if ($status === '1' || $status === 1 || $status === 'ok') {
            // Платеж успешен
            $paymentSession->markAsPaid();

            // Пополняем баланс пользователя
            $paymentSession->user->addFunds(
                $paymentSession->amount,
                'Пополнение через FreedomPay',
                $paymentSession->order_id
            );

            Log::info('Payment completed successfully', [
                'order_id' => $orderId,
                'amount' => $paymentSession->amount,
                'user_id' => $paymentSession->user_id,
            ]);

        } else {
            // Платеж неуспешен
            $paymentSession->update(['status' => 'failed']);

            Log::info('Payment failed', [
                'order_id' => $orderId,
                'result' => $status,
                'data' => $data,
            ]);
        }

        return true;
    }

    /**
     * Генерировать соль
     */
    private function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Генерировать подпись точно как в JavaScript примере
     */
    private function generateSignature(array $params, string $scriptName = 'init_payment.php'): string
    {
        // Сортируем параметры по ключам (как в JS: Object.keys(params).sort())
        ksort($params);

        // Формируем массив для подписи: [script_name, value1, value2, ..., secret_key]
        $signParts = [$scriptName];

        // Добавляем значения параметров в отсортированном порядке
        foreach ($params as $value) {
            $signParts[] = (string) $value;
        }

        // Добавляем секретный ключ в конец
        $signParts[] = $this->secretKey;

        // Генерируем строку для подписи (как в JS: signParts.join(';'))
        $signatureString = implode(';', $signParts);

        Log::debug('FreedomPay signature generation', [
            'params' => $params,
            'script_name' => $scriptName,
            'sign_parts' => $signParts,
            'signature_string' => $signatureString,
            'signature' => md5($signatureString),
        ]);

        return md5($signatureString);
    }



    /**
     * Проверить подпись callback
     */
    private function verifySignature(array $data): bool
    {
        $signature = $data['pg_sig'] ?? '';
        unset($data['pg_sig']);

        // Для callback используется другой скрипт
        $expectedSignature = $this->generateSignature($data, 'result');

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Парсить XML ответ от FreedomPay
     */
    private function parseXmlResponse(string $xmlString): array
    {
        try {
            // Убираем BOM и лишние пробелы
            $xmlString = trim($xmlString);
            if (substr($xmlString, 0, 3) === "\xEF\xBB\xBF") {
                $xmlString = substr($xmlString, 3);
            }

            $xml = simplexml_load_string($xmlString);

            if ($xml === false) {
                throw new \Exception('Invalid XML response');
            }

            // Конвертируем SimpleXML в массив
            $array = json_decode(json_encode($xml), true);

            Log::info('FreedomPay XML response parsed', [
                'original_xml' => $xmlString,
                'parsed_array' => $array,
            ]);

            return $array;

        } catch (\Exception $e) {
            Log::error('Failed to parse FreedomPay XML response', [
                'xml' => $xmlString,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Ошибка парсинга ответа от FreedomPay: ' . $e->getMessage());
        }
    }

    /**
     * Тестовый метод для проверки генерации подписи
     * Можно использовать для отладки
     */
    public function testSignatureGeneration(): array
    {
        $testParams = [
            'pg_order_id' => 'ORDER-001',
            'pg_merchant_id' => $this->merchantId,
            'pg_amount' => '400',
            'pg_description' => 'Тестовая оплата',
            'pg_salt' => 'test_salt_123',
            'pg_currency' => 'KZT',
        ];

        // Сортируем параметры по ключам
        ksort($testParams);

        // Формируем массив для подписи точно как в JS
        $signParts = ['init_payment.php'];
        foreach ($testParams as $value) {
            $signParts[] = (string) $value;
        }
        $signParts[] = $this->secretKey;

        $signatureString = implode(';', $signParts);
        $signature = md5($signatureString);

        // Добавляем подпись к параметрам
        $testParams['pg_sig'] = $signature;

        // Формируем URL-encoded строку как в Postman
        $urlEncodedData = http_build_query($testParams);

        return [
            'original_params' => $testParams,
            'sorted_params_for_signature' => array_slice($testParams, 0, -1), // без подписи
            'script_name' => 'init_payment.php',
            'secret_key' => $this->secretKey ? 'SET (' . substr($this->secretKey, 0, 4) . '...)' : 'NOT_SET',
            'sign_parts' => $signParts,
            'signature_string' => $signatureString,
            'signature' => $signature,
            'url_encoded_data' => $urlEncodedData,
            'postman_equivalent' => [
                'description' => 'Postman Body (x-www-form-urlencoded):',
                'body_format' => $urlEncodedData,
                'content_type' => 'application/x-www-form-urlencoded',
            ],
        ];
    }
}