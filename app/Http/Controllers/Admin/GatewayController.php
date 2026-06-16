<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GatewayController extends Controller
{
    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $gateways = Gateway::withCount('currencies')
            ->latest()
            ->get()
            ->map(fn ($g) => [
                'id'                  => $g->id,
                'name'                => $g->name,
                'alias'               => $g->alias,
                'code'                => $g->code,
                'image'               => $g->image ? asset("storage/gateways/{$g->image}") : null,
                'status'              => $g->status,
                'currencies_count'    => $g->currencies_count,
                'supported_currencies'=> $g->supported_currencies ?? [],
            ]);

        return Inertia::render('Admin/GatewayIndex', compact('gateways'));
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function create()
    {
     
        return Inertia::render('Gateway/GatewayForm');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:100',
            'alias'                => 'required|string|max:100',
            'code'                 => 'required|string|max:100|unique:gateways,code',
            'image'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description'          => 'nullable|string|max:500',
            'status'               => 'required|in:0,1',
            'parameters'           => 'required|json',
            'supported_currencies' => 'required|json',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = $request->file('image')->store('gateways', 'public');
        }

        Gateway::create([
            'name'                 => $validated['name'],
            'alias'                => $validated['alias'],
            'code'                 => $validated['code'],
            'image'                => $imageName,
            'description'          => $validated['description'],
            'status'               => $validated['status'],
            'parameters'           => json_decode($validated['parameters'], true),
            'supported_currencies' => json_decode($validated['supported_currencies'], true),
        ]);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Gateway created successfully.');
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(Gateway $gateway): Response
    {
        return Inertia::render('Admin/GatewayForm', [
            'gateway' => [
                'id'                  => $gateway->id,
                'name'                => $gateway->name,
                'alias'               => $gateway->alias,
                'code'                => $gateway->code,
                'image'               => $gateway->image,
                'description'         => $gateway->description,
                'status'              => $gateway->status,
                // Cast JSON columns → arrays for the Vue component
                'parameters'          => $gateway->parameters           ?? [],
                'supported_currencies'=> $gateway->supported_currencies ?? [],
            ],
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, Gateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:100',
            'alias'                => 'required|string|max:100',
            'code'                 => "required|string|max:100|unique:gateways,code,{$gateway->id}",
            'image'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description'          => 'nullable|string|max:500',
            'status'               => 'required|in:0,1',
            'parameters'           => 'required|json',
            'supported_currencies' => 'required|json',
        ]);

        if ($request->hasFile('image')) {
            // Remove old image if it exists
            if ($gateway->image) {
                Storage::disk('public')->delete("gateways/{$gateway->image}");
            }
            $validated['image'] = $request->file('image')->store('gateways', 'public');
        }

        $gateway->update([
            'name'                 => $validated['name'],
            'alias'                => $validated['alias'],
            'code'                 => $validated['code'],
            'image'                => $validated['image'] ?? $gateway->image,
            'description'          => $validated['description'],
            'status'               => $validated['status'],
            'parameters'           => json_decode($validated['parameters'], true),
            'supported_currencies' => json_decode($validated['supported_currencies'], true),
        ]);

        return redirect()->route('admin.gateways.index')
            ->with('success', 'Gateway updated successfully.');
    }
}
