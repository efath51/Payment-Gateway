<?php

namespace App\Http\Controllers\Gateway\Sslcommerz;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProcessController
{
    private static string $storeId;
    private static string $storePassword;
    private static string $baseUrl;

    public static function process($deposit): string
    {
        $gatewayCurrency = $deposit->gatewayCurrency();
        $method          = $gatewayCurrency->method;
        $parameters      = json_decode($method->parameters);

        self::$storeId       = $parameters->store_id->value;
        self::$storePassword = $parameters->store_password->value;
        self::$baseUrl       = self::getBaseUrl();

        $user     = $deposit->user;
        $postData = [
            'store_id'         => self::$storeId,
            'store_passwd'     => self::$storePassword,
            'total_amount'     => round($deposit->final_amount, 2),
            'currency'         => $deposit->method_currency,
            'tran_id'          => $deposit->trx,

            'success_url'      => route('payment.success', $deposit->trx),
            'fail_url'         => route('payment.fail', $deposit->trx),
            'cancel_url'       => route('payment.cancel', $deposit->trx),
            'ipn_url'          => route('ipn.sslcommerz'),

            'cus_name'         => $user->name    ?? 'Customer',
            'cus_email'        => $user->email   ?? 'customer@example.com',
            'cus_phone'        => $user->phone   ?? '01700000000',
            'cus_add1'         => $user->address ?? 'Dhaka',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'ship_name'        => $user->name    ?? 'Customer',
            'ship_add1'        => $user->address ?? 'Dhaka',
            'ship_city'        => 'Dhaka',
            'ship_country'     => 'Bangladesh',

            'product_name'     => 'Deposit',
            'product_category' => 'General',
            'product_amount'   => round($deposit->final_amount, 2),
        ];

        // This is a server-side HTTP call — no CORS involved here
        try {
            $response = Http::asForm()->post(self::$baseUrl . '/gwprocess/v4/api.php', $postData);


        } catch (\Exception $e) {
          
            return json_encode(['error' => true, 'message' => 'Could not connect to payment gateway.']);
        }

        if (!$response->successful()) {
                    return json_encode(['error' => true, 'message' => 'Payment gateway is unavailable.']);
        }

        $responseData = $response->json();

        if (!isset($responseData['status']) || $responseData['status'] !== 'SUCCESS') {
            $reason = $responseData['failedreason'] ?? 'Payment initialization failed.';
           
            return json_encode(['error' => true, 'message' => $reason]);
        }

        return json_encode([
            'redirect'     => true,
            'redirect_url' => $responseData['GatewayPageURL'],
        ]);
    }
    public function ipn(Request $request)
    {
        $trx = $request->tran_id;
        if (!$trx) {
            return response()->json(['status' => 'error', 'message' => 'Missing tran_id'], 400);
        }
        $deposit = Deposit::where('trx', $trx)
            ->where('status', Status::PAYMENT_INITIATE)
            ->first();

        if (!$deposit) {
            return response()->json(['status' => 'already_processed']);
        }

        if (!$this->validatePayment($deposit, $request)) {

            return response()->json(['status' => 'validation_failed'], 400);
        }

        PaymentController::userDataUpdate($deposit);
        return response()->json(['status' => 'success']);
    }



    private function validatePayment($deposit, $request)
    {
        $gatewayCurrency = $deposit->gatewayCurrency();
        $method = $gatewayCurrency->method;
        $parameters = json_decode($method->parameters); 

        $validationUrl = self::getBaseUrl() . '/validator/api/validationserverAPI.php';

        $response = Http::asForm()->post($validationUrl, [
            'val_id' => $request->val_id,
            'store_id' => $parameters->store_id->value, 
            'store_passwd' => $parameters->store_password->value, 
            'format' => 'json'
        ]);

        if (!$response->successful()) {
            return false;
        }

        $verification = $response->json();

        return isset($verification['status']) && $verification['status'] == 'VALID';
    }
    private static function getBaseUrl()
    {

        if (config('app.env') === 'production') {
            return 'https://securepay.sslcommerz.com';
        }
        return 'https://sandbox.sslcommerz.com';
    }
}
