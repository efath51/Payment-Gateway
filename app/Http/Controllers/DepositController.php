<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GatewayCurrency;
use App\Traits\DebugBackTrait;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepositController extends Controller
{
    use DebugBackTrait;
    public function index(): Response
    {
        $gateways = GatewayCurrency::with('method')
            ->whereHas('method')   // Status::ENABLE
            ->get()
            ->map(fn ($g) => [
                'id'              => $g->id,
                'method_code'     => $g->method_code,
                'currency'        => strtoupper($g->currency),
                'method_name'     => $g->method->name,
                'min_amount'      => (float) $g->min_amount,
                'max_amount'      => (float) $g->max_amount,
                'fixed_charge'    => (float) $g->fixed_charge,
                'percent_charge'  => (float) $g->percent_charge,
                'rate'            => (float) $g->rate,
            ]);

        return Inertia::render('Deposit/DepositTest', [
            'gateways' => $gateways,
        ]);
    }

    // ── Receive & inspect form data ───────────────────────────────────────────

    public function store(Request $request)
    {
        // 1. Validate ──────────────────────────────────────────────────────────
        $validated = $request->validate([
            'gateway'               => 'required|string',
            'currency'              => 'required|string',
            'amount'                => 'required|numeric|gt:0',

            // Customer
            'cus_name'              => 'required|string|max:100',
            'cus_email'             => 'required|email|max:150',
            'cus_phone'             => 'required|string|max:20',
            'cus_add1'              => 'required|string|max:200',
            'cus_city'              => 'required|string|max:100',
            'cus_country'           => 'required|string|max:100',

            // Products (the Postman-style rows)
            'products'              => 'required|array|min:1',
            'products.*.name'       => 'required|string|max:200',
            'products.*.category'   => 'required|string|max:100',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.quantity'   => 'required|integer|min:1',

            // Optional references
            'plan_id'               => 'nullable|integer|min:1',
            'donation_id'           => 'nullable|integer|min:1',
            'order_id'              => 'nullable|integer|min:1',
        ]);
        // dd($validated);
        // 2. Resolve gateway ───────────────────────────────────────────────────
        $gate = GatewayCurrency::with('method')
            // ->whereHas('method', fn($q) => $q->where('status', 1))
            ->where('method_code', 101)
            ->where('currency',$validated['currency'])
            ->first();

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('is_active',1);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();



        if (! $gate) {
            return back()->withErrors(['gateway' => 'Gateway not found or is currently disabled.']);
        }

        // 3. Enforce min/max limits ────────────────────────────────────────────
        $amount = (float) $validated['amount'];

        if ($amount < $gate->min_amount || $amount > $gate->max_amount) {
            return back()->withErrors([
                'amount' => "Amount must be between {$gate->min_amount} and {$gate->max_amount} {$gate->currency}.",
            ]);
        }

        // 4. Compute charges (same logic as depositInsert) ────────────────────
        $charge      = $gate->fixed_charge + ($amount * $gate->percent_charge / 100);
        $payable     = $amount + $charge;
        $finalAmount = round($payable * $gate->rate, 2);

        // 5. Build debug payload — this is what would go into the Deposit model ─
        $debugPayload = [
            'input' => [
                'amount'      => $amount,
                'gateway'     => $validated['gateway'],
                'currency'    => $validated['currency'],
                'plan_id'     => $validated['plan_id']     ?? 0,
                'donation_id' => $validated['donation_id'] ?? 0,
                'order_id'    => $validated['order_id']    ?? 0,
            ],
            'customer' => [
                'name'    => $validated['cus_name'],
                'email'   => $validated['cus_email'],
                'phone'   => $validated['cus_phone'],
                'address' => $validated['cus_add1'],
                'city'    => $validated['cus_city'],
                'country' => $validated['cus_country'],
            ],
            'products' => $validated['products'],

            'gateway' => [
                'name'            => $gate->name,
                'code'            => $gate->method_code,
                'currency'        => strtoupper($gate->currency),
                'fixed_charge'    => $gate->fixed_charge,
                'percent_charge'  => $gate->percent_charge,
                'rate'            => $gate->rate,
                'min_amount'      => $gate->min_amount,
                'max_amount'      => $gate->max_amount,
            ],
            'computed' => [
                'subtotal'     => $amount,
                'charge'       => round($charge, 2),
                'payable'      => round($payable, 2),
                'final_amount' => $finalAmount,
                'currency'     => strtoupper($gate->currency),
            ],
            // Simulate what the Deposit model would look like
            'deposit_preview' => [
                'user_id'         => auth()->id(),
                'method_code'     => $gate->method_code,
                'method_currency' => strtoupper($gate->currency),
                'amount'          => $amount,
                'charge'          => round($charge, 2),
                'rate'            => $gate->rate,
                'final_amount'    => $finalAmount,
                'status'          => 'INITIATE',
                'trx'             => '[would be generated by getTrx()]',
            ],
        ];

        return back()->with('result', $debugPayload);
    }
}
