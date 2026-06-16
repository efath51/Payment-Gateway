<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function CreateGateway()
    {
        return Inertia::render('Gateway/CreateGateway');
    }

    public function CreateDeposit()
    {
        return Inertia::render('Gateway/dataTesting');
    }


    public function depositInsert(Request $request)
    {
        try {

            $request->validate([
                'amount' => 'required|numeric|gt:0',
                'gateway' => 'required',
                'currency' => 'required',
            ]);

            $gate = GatewayCurrency::whereHas('method', function ($query) {
                $query->where('status', Status::ENABLE);
            })->where('method_code', $request->gateway)
                ->where('currency', $request->currency)
                ->first();

            if (!$gate) {
                return back()->with('error', 'Invalid gateway selected');
            }

            // Validate min/max amount
            if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
                return back()->with('error', 'Amount does not match gateway limit');
            }

            // Calculate charges
            $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
            $payable = $request->amount + $charge;
            $finalAmount = $payable * $gate->rate;

            // Create deposit record
            $deposit = new Deposit();
            $deposit->user_id = auth()->id();
            $deposit->plan_id = $request->plan_id ?? 0;

            $deposit->order_id = $request->order_id ?? 0;
            $deposit->method_code = $gate->method_code;
            $deposit->method_currency = strtoupper($gate->currency);
            $deposit->amount = $request->amount;
            $deposit->charge = $charge;
            $deposit->rate = $gate->rate;
            $deposit->final_amount = $finalAmount;
            $deposit->trx = getTrx();
            $deposit->status = Status::PAYMENT_INITIATE;
            $deposit->save();

            session()->put('Track', $deposit->trx);
        } catch (Exception $e) {
            dd($e);     //here need to change
        }
        // Redirect to confirmation
        return redirect()->route('user.deposit.confirm');
    }

    public function depositConfirm(Request $request)
    {
        $track = session()->get('Track');

        if (!$track) {
            return redirect()->route('user.deposit.index')
                ->with('error', 'Session expired. Please start your deposit again.');
        }

        $deposit = Deposit::where('trx', $track)
            ->where('status', Status::PAYMENT_INITIATE)
            ->with('gateway')
            ->first();

        if (!$deposit) {
            return redirect()->route('user.deposit.index')
                ->with('error', 'Deposit not found or already processed.');
        }



        $gatewayName = ucfirst($deposit->gateway->alias);
        $className   = "App\\Http\\Controllers\\Gateway\\{$gatewayName}\\ProcessController";

        if (!class_exists($className)) {
            return redirect()->route('user.deposit.index')
                ->with('error', 'Payment gateway is not available.');
        }

        // Wrap process() — it calls an external API that can throw or return garbage
        try {
            $data = json_decode($className::process($deposit));
        } catch (\Exception $e) {

            return redirect()->route('user.deposit.index')
                ->with('error', 'Gateway error. Please try again or contact support.');
        }

        // json_decode returns null if the response was not valid JSON
        if (!$data) {

            return redirect()->route('user.deposit.index')
                ->with('error', 'Invalid gateway response. Please try again.');
        }

        if (!empty($data->error)) {
            return redirect()->route('user.deposit.index')
                ->with('error', $data->message ?? 'Payment could not be initiated.');
        }

        if (!empty($data->redirect)) {

            if ($request->header('X-Inertia')) {
                return Inertia::location($data->redirect_url);
            }

            // Non-Inertia (regular browser visit) — standard redirect
            return redirect()->away($data->redirect_url);
        }

        return redirect()->route('user.deposit.index');
    }

    public static function userDataUpdate($deposit)
    {
        // Only update if status is INITIATE or PENDING
        if ($deposit->status != Status::PAYMENT_INITIATE && $deposit->status != Status::PAYMENT_PENDING) {
            return false;
        }

        // Update deposit status
        $deposit->status = Status::PAYMENT_SUCCESS;
        $deposit->save();

        // Get user
        $user = User::find($deposit->user_id);


        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $deposit->amount;
        $transaction->charge = $deposit->charge;
        $transaction->details = 'Payment via ' . $deposit->gatewayCurrency()->name;
        $transaction->trx = $deposit->trx;
        $transaction->remark = 'Payment';
        $transaction->save();

        return true;
    }

    public function deposit()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->get();

        $pageTitle = 'Deposit Methods';
        return view('user.deposit.index', compact('gatewayCurrency', 'pageTitle'));
    }

    public function paymentSuccess(Request $request, $trx = null)
    {
        // SSLCommerz POSTs tran_id, but we also support trx from the route param
        $trx = $trx ?? $request->input('tran_id');

        if (!$trx) {
            return redirect()->route('home')
                ->with('error', 'Invalid payment reference.');
        }


        $deposit = Deposit::where('trx', $trx)->first();

        if (!$deposit) {
            return redirect()->route('login')
                ->with('error', 'Deposit record not found.');
        }

        if (!auth()->check()) {
            $user = User::find($deposit->user_id);
            if ($user) {
                auth()->login($user);
            }
        }
        return redirect()->route('user.deposit.index')->with('success', 'Payment confirmed successfully!');

        
    }

    public function paymentFail(Request $request, $trx = null)
    {
        session()->forget('Track');
        return redirect()->route('user.deposit.index')
            ->with('error', 'Payment failed. Please try again or use a different method.');
    }

    public function paymentCancel(Request $request, $trx = null)
    {
        session()->forget('Track');
        return redirect()->route('user.deposit.index')
            ->with('error', 'Payment was cancelled.');
    }
}
