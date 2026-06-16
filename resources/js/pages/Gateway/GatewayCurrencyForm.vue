<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface GatewayOption {
    id: number;
    name: string;
}

interface CurrencyData {
    id?: number;
    gateway_id: number;
    name: string;
    currency: string;
    method_code: string | number;
    symbol: string;
    rate: number;
    min_amount: number;
    max_amount: number;
    fixed_charge: number;
    percent_charge: number;
    is_active: number;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    gateways: GatewayOption[];
    currency?: CurrencyData;   // present = edit mode, absent = create mode
}>();

const isEdit = computed(() => !!props.currency?.id);

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
    gateway_id:      props.currency?.gateway_id      ?? (props.gateways[0]?.id ?? ''),
    name:            props.currency?.name            ?? '',
    currency:        props.currency?.currency        ?? '',
    method_code:     props.currency?.method_code     ?? '',
    symbol:          props.currency?.symbol          ?? '',
    rate:            props.currency?.rate            ?? 1,
    min_amount:      props.currency?.min_amount      ?? 1,
    max_amount:      props.currency?.max_amount      ?? 10000,
    fixed_charge:    props.currency?.fixed_charge    ?? 0,
    percent_charge:  props.currency?.percent_charge  ?? 0,
    is_active:       props.currency?.is_active       ?? 1,
});

// ─── Live charge preview (based on a $100 sample) ────────────────────────────

const SAMPLE = 100;

const previewCharge  = computed(() =>
    Number(form.fixed_charge) + (SAMPLE * Number(form.percent_charge)) / 100,
);
const previewPayable = computed(() => SAMPLE + previewCharge.value);
const previewFinal   = computed(() => (previewPayable.value * Number(form.rate)).toFixed(4));

// ─── Submit ───────────────────────────────────────────────────────────────────

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.gateway-currencies.update', props.currency!.id));
    } else {
        form.post(route('admin.gateway-currencies.store'));
    }
};

// ─── Meta ─────────────────────────────────────────────────────────────────────

const breadcrumbs = [
    { title: 'Gateways', href: route('admin.gateways.index') },
    { title: isEdit.value ? 'Edit Currency' : 'Add Currency', href: '#' },
];

const input = 'w-full rounded-lg border border-neutral-800 bg-neutral-900 px-3 py-2.5 text-sm text-gray-200 placeholder-gray-600 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
const label = 'mb-1.5 block text-xs font-medium text-gray-400';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full px-4 py-6">

            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">
                        {{ isEdit ? 'Edit Currency' : 'Add Currency' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ isEdit ? 'Update rate, limits, and charge settings.' : 'Attach a new currency to a gateway.' }}
                    </p>
                </div>
                <button
                    @click="router.visit(route('admin.gateways.index'))"
                    class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-400 transition hover:border-gray-600 hover:text-gray-200"
                >
                    ← Back to Gateways
                </button>
            </div>

            <div class="grid grid-cols-5 gap-4">

                <!-- ══ LEFT col-span-3 ══════════════════════════════════════ -->
                <div class="col-span-3 space-y-4">

                    <!-- Identity ────────────────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-6 py-4">
                            <h2 class="font-semibold text-white">Identity</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Which gateway this currency belongs to, and how it's identified.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 px-6 py-5">

                            <!-- Gateway selector -->
                            <div class="col-span-2">
                                <label :class="label">Gateway</label>
                                <select v-model.number="form.gateway_id" :class="input">
                                    <option v-for="gw in gateways" :key="gw.id" :value="gw.id">
                                        {{ gw.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.gateway_id" class="mt-1 text-xs text-red-400">{{ form.errors.gateway_id }}</p>
                            </div>

                            <!-- Display name -->
                            <div class="col-span-2">
                                <label :class="label">Display Name</label>
                                <input v-model="form.name" type="text" placeholder="e.g. Stripe USD" :class="input" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <!-- Currency code -->
                            <div>
                                <label :class="label">Currency Code</label>
                                <input v-model="form.currency" type="text" placeholder="USD" :class="input + ' uppercase'" maxlength="10" />
                                <p v-if="form.errors.currency" class="mt-1 text-xs text-red-400">{{ form.errors.currency }}</p>
                            </div>

                            <!-- Symbol -->
                            <div>
                                <label :class="label">Symbol</label>
                                <input v-model="form.symbol" type="text" placeholder="$" :class="input" maxlength="5" />
                                <p v-if="form.errors.symbol" class="mt-1 text-xs text-red-400">{{ form.errors.symbol }}</p>
                            </div>

                            <!-- Method code -->
                            <div class="col-span-2">
                                <label :class="label">
                                    Method Code
                                    <span class="ml-1 text-gray-600">— numeric or string used by the processor</span>
                                </label>
                                <input v-model="form.method_code" type="text" placeholder="e.g. 101" :class="input + ' font-mono'" />
                                <p v-if="form.errors.method_code" class="mt-1 text-xs text-red-400">{{ form.errors.method_code }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- Rate & Limits ────────────────────────────────────────── -->
                    <div class="rounded-lg border  border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-6 py-4">
                            <h2 class="font-semibold text-white">Rate & Amount Limits</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Conversion rate and the allowed deposit range.</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-5">

                            <div class="col-span-3">
                                <label :class="label">
                                    Exchange Rate
                                    <span class="ml-1 text-gray-600">— 1 base unit = X {{ form.currency || 'currency' }}</span>
                                </label>
                                <input v-model.number="form.rate" type="number" min="0.0001" step="0.0001" :class="input" />
                                <p v-if="form.errors.rate" class="mt-1 text-xs text-red-400">{{ form.errors.rate }}</p>
                            </div>

                            <div>
                                <label :class="label">Min Amount</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">{{ form.symbol || '$' }}</span>
                                    <input v-model.number="form.min_amount" type="number" min="0" step="0.01" :class="input + ' pl-7'" />
                                </div>
                                <p v-if="form.errors.min_amount" class="mt-1 text-xs text-red-400">{{ form.errors.min_amount }}</p>
                            </div>

                            <div>
                                <label :class="label">Max Amount</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">{{ form.symbol || '$' }}</span>
                                    <input v-model.number="form.max_amount" type="number" min="0" step="0.01" :class="input + ' pl-7'" />
                                </div>
                                <p v-if="form.errors.max_amount" class="mt-1 text-xs text-red-400">{{ form.errors.max_amount }}</p>
                            </div>

                            <!-- Range bar (visual only) -->
                            <div class="col-span-3">
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span>{{ form.symbol }}{{ form.min_amount }}</span>
                                    <span class="text-gray-700">allowed range</span>
                                    <span>{{ form.symbol }}{{ form.max_amount }}</span>
                                </div>
                                <div class="mt-1 h-1 rounded-full bg-gray-700">
                                    <div class="h-1 w-full rounded-full bg-blue-600/40" />
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <!-- ══ END LEFT ══════════════════════════════════════════════ -->

                <!-- ══ RIGHT col-span-2 ══════════════════════════════════════ -->
                <div class="col-span-2 space-y-4">

                    <!-- Charges ──────────────────────────────────────────────── -->
                    <div class="rounded-lg border  border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Charge Settings</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Applied on top of every deposit amount.</p>
                        </div>
                        <div class="space-y-4 px-5 py-4">

                            <div>
                                <label :class="label">Fixed Charge</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">{{ form.symbol || '$' }}</span>
                                    <input v-model.number="form.fixed_charge" type="number" min="0" step="0.01" :class="input + ' pl-7'" />
                                </div>
                                <p v-if="form.errors.fixed_charge" class="mt-1 text-xs text-red-400">{{ form.errors.fixed_charge }}</p>
                            </div>

                            <div>
                                <label :class="label">Percent Charge</label>
                                <div class="relative">
                                    <input v-model.number="form.percent_charge" type="number" min="0" max="100" step="0.01" :class="input + ' pr-7'" />
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-gray-500">%</span>
                                </div>
                                <p v-if="form.errors.percent_charge" class="mt-1 text-xs text-red-400">{{ form.errors.percent_charge }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- Live charge preview ───────────────────────────────────── -->
                    <div class="rounded-lg border  border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Charge Preview</h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Sample on a
                                <span class="font-medium text-gray-400">{{ form.symbol }}{{ SAMPLE }}</span>
                                deposit.
                            </p>
                        </div>
                        <div class="space-y-2.5 px-5 py-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Amount</span>
                                <span class="text-gray-300">{{ form.symbol }}{{ SAMPLE }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    Charge
                                    <span class="text-gray-700 text-xs">({{ form.fixed_charge }} + {{ form.percent_charge }}%)</span>
                                </span>
                                <span class="text-gray-300">{{ form.symbol }}{{ previewCharge.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-700 pt-2.5">
                                <span class="text-gray-400 font-medium">Payable</span>
                                <span class="text-white font-semibold">{{ form.symbol }}{{ previewPayable.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Converted <span class="text-gray-700 text-xs">(× {{ form.rate }})</span></span>
                                <span class="font-semibold text-emerald-400">{{ form.currency || '—' }} {{ previewFinal }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status ───────────────────────────────────────────────── -->
                    <div class="rounded-lg border  border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Status</h2>
                        </div>
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <p class="text-sm font-medium" :class="form.is_active === 1 ? 'text-emerald-400' : 'text-gray-400'">
                                    {{ form.is_active === 1 ? 'Active' : 'Inactive' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ form.is_active === 1 ? 'Available for transactions.' : 'Disabled — no transactions.' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="form.is_active = form.is_active === 1 ? 0 : 1"
                                class="relative h-6 w-11 rounded-full transition-colors"
                                :class="form.is_active === 1 ? 'bg-blue-600' : 'bg-gray-700'"
                            >
                                <span
                                    class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition-all"
                                    :class="form.is_active === 1 ? 'left-6' : 'left-1'"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Actions ──────────────────────────────────────────────── -->
                    <div class="rounded-lg border border-gray-700 bg-neutral-900 px-5 py-4">
                        <div class="flex flex-col gap-2">
                            <button
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Currency') }}
                            </button>
                            <button
                                @click="router.visit(route('admin.gateways.index'))"
                                type="button"
                                class="w-full rounded-lg border border-gray-700 px-5 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:bg-gray-800 hover:text-gray-200"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                </div>
                <!-- ══ END RIGHT ══════════════════════════════════════════════ -->

            </div>
        </div>
    </AppLayout>
</template>
