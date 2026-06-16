<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatewayCurrencyController extends Controller
{
    // ── Create form ───────────────────────────────────────────────────────────

    public function create(Request $request): Response
    {
        return Inertia::render('Gateway/GatewayCurrencyForm', [
            'gateways' => Gateway::where('status', 1)->get(['id', 'name']),
            // Pre-select a gateway if ?gateway_id=X is in the URL
            'currency' => $request->filled('gateway_id')
                ? ['gateway_id' => $request->integer('gateway_id')]
                : null,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gateway_id'     => 'required|exists:gateways,id',
            'name'           => 'required|string|max:100',
            'currency'       => 'required|string|max:10',
            'method_code'    => 'required|max:50',
            'symbol'         => 'required|string|max:10',
            'rate'           => 'required|numeric|gt:0',
            'min_amount'     => 'required|numeric|gt:0',
            'max_amount'     => 'required|numeric|gt:0|gte:min_amount',
            'fixed_charge'   => 'required|numeric|min:0',
            'percent_charge' => 'required|numeric|min:0|max:100',
            'is_active'      => 'required|in:0,1',
        ]);

        GatewayCurrency::create($validated);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Currency added successfully.');
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(GatewayCurrency $currency): Response
    {
        return Inertia::render('Admin/GatewayCurrencyForm', [
            'gateways' => Gateway::where('status', 1)->get(['id', 'name']),
            'currency' => $currency,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, GatewayCurrency $currency): RedirectResponse
    {
        $validated = $request->validate([
            'gateway_id'     => 'required|exists:gateways,id',
            'name'           => 'required|string|max:100',
            'currency'       => 'required|string|max:10',
            'method_code'    => 'required|max:50',
            'symbol'         => 'required|string|max:10',
            'rate'           => 'required|numeric|gt:0',
            'min_amount'     => 'required|numeric|gt:0',
            'max_amount'     => 'required|numeric|gt:0|gte:min_amount',
            'fixed_charge'   => 'required|numeric|min:0',
            'percent_charge' => 'required|numeric|min:0|max:100',
            'is_active'      => 'required|in:0,1',
        ]);

        $currency->update($validated);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Currency updated successfully.');
    }
}
