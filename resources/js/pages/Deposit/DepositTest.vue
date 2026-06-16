<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface ProductLine {
    id: string;
    name: string;
    category: string;
    unit_price: number;
    quantity: number;
}

interface GatewayCurrency {
    id: number;
    method_code: string;
    currency: string;
    method_name: string;
    min_amount: number;
    max_amount: number;
    fixed_charge: number;
    percent_charge: number;
    rate: number;
}

interface PageProps {
    gateways: GatewayCurrency[];
    flash: { result?: Record<string, unknown> };
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{ gateways: GatewayCurrency[] }>();
const page = usePage<PageProps>();

// ─── Product lines ────────────────────────────────────────────────────────────

const newProduct = (): ProductLine => ({
    id: '',
    name: '',
    category: 'General',
    unit_price: 0,
    quantity: 1,
});

const products = ref<ProductLine[]>([newProduct()]);

const addProduct = () => products.value.push(newProduct());
const removeProduct = (id: string) => {
    if (products.value.length > 1) {
        products.value = products.value.filter((p) => p.id !== id);
    }
};

// ─── Gateway selection ────────────────────────────────────────────────────────

const selectedGateway = ref<GatewayCurrency | null>(null);

const selectGateway = (gw: GatewayCurrency) => {
    selectedGateway.value = selectedGateway.value?.id === gw.id ? null : gw;
    form.gateway = selectedGateway.value?.method_code ?? '';
    form.currency = selectedGateway.value?.currency ?? '';
};

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
    gateway: '',
    currency: '',
    amount: 0,
    cus_name: 'Testewqe Customer',
    cus_email: 'test@example.com',
    cus_phone: '01700000000',
    cus_add1: 'Dhaka',
    cus_city: 'Dhaka',
    cus_country: 'Bangladesh',
    plan_id: null as number | null,
    donation_id: null as number | null,
    order_id: null as number | null,
    products: [] as ProductLine[],
});

// ─── Computed summary ─────────────────────────────────────────────────────────

const totalAmount = computed(() =>
    products.value.reduce((sum, p) => sum + p.unit_price * p.quantity, 0),
);

const charge = computed(() => {
    if (!selectedGateway.value) return 0;
    const g = selectedGateway.value;
    return g.fixed_charge + (totalAmount.value * g.percent_charge) / 100;
});

const payable   = computed(() => totalAmount.value + charge.value);
const finalAmount = computed(() =>
    selectedGateway.value ? payable.value * selectedGateway.value.rate : payable.value,
);

// ─── Flash result ─────────────────────────────────────────────────────────────

const result     = computed(() => page.props.flash?.result ?? null);
const resultJson = computed(() => (result.value ? JSON.stringify(result.value, null, 2) : ''));

// ─── Submit ───────────────────────────────────────────────────────────────────

const submit = () => {
    // alert('dafds');
    form.amount   = totalAmount.value;
    form.products = products.value;
    form.post(route('user.deposit.store'), { preserveScroll: true });
};

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────

const breadcrumbs = [
    { title: 'Dev Tools', href: '#' },
    { title: 'Deposit Tester', href: '/dev/deposit' },
];

// ─── Shared input class ───────────────────────────────────────────────────────
// Mirrors the image: inputs are darker than the card background so they read as
// "inset", with a single subtle border and no heavy ring at rest.
const inputCls =
    'w-full rounded-lg border border-neutral-800 bg-neutral-900 px-3 py-2.5 text-sm ' +
    'text-gray-200 placeholder-gray-600 outline-none transition ' +
    'focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full px-4 py-10">

            <!-- ── Header ─────────────────────────────────────────────────── -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">Deposit Tester</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Simulate a deposit flow — product data, customer info, gateway. Just like Postman.
                    </p>
                </div>
                <span class="rounded-lg border border-amber-700 bg-amber-950 px-3 py-1 text-xs font-medium text-amber-400">
                    Dev / Test Mode
                </span>
            </div>

            <!-- ── Two-column layout (3 + 2) ─────────────────────────────── -->
            <div class="grid grid-cols-5 gap-4">

                <!-- ══ LEFT COLUMN ══════════════════════════════════════════ -->
                <div class="col-span-3 space-y-4">

                    <!-- Section 1 · Product Lines ─────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="flex items-start justify-between border-b border-neutral-800 px-6 py-4">
                            <div>
                                <h2 class="font-semibold text-white">Product Lines</h2>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Mimics
                                    <code class="rounded bg-gray-700 px-1 text-gray-300">product_name</code> /
                                    <code class="rounded bg-gray-700 px-1 text-gray-300">product_amount</code> fields.
                                </p>
                            </div>
                            <button
                                @click="addProduct"
                                class="flex items-center gap-1 rounded-lg border border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-400 transition hover:border-gray-500 hover:bg-gray-700 hover:text-gray-200"
                            >
                                + Add Row
                            </button>
                        </div>

                        <div class="px-6 py-4">
                            <!-- Column headers -->
                            <div class="mb-2 grid grid-cols-12 gap-3 px-0.5 text-xs font-medium uppercase tracking-wider text-gray-600">
                                <span class="col-span-5">Product Name</span>
                                <span class="col-span-3">Category</span>
                                <span class="col-span-2">Unit Price</span>
                                <span class="col-span-1 text-center">Qty</span>
                                <span class="col-span-1"></span>
                            </div>

                            <!-- Rows -->
                            <div
                                v-for="(product, i) in products"
                                :key="product.id"
                                class="mb-2 grid grid-cols-12 items-center gap-3"
                            >
                                <input
                                    v-model="product.name"
                                    type="text"
                                    :placeholder="i === 0 ? 'e.g. Premium Plan' : 'Product name'"
                                    :class="inputCls + ' col-span-5'"
                                />
                                <input
                                    v-model="product.category"
                                    type="text"
                                    placeholder="General"
                                    :class="inputCls + ' col-span-3'"
                                />
                                <input
                                    v-model.number="product.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    :class="inputCls + ' col-span-2'"
                                />
                                <input
                                    v-model.number="product.quantity"
                                    type="number"
                                    min="1"
                                    :class="inputCls + ' col-span-1 text-center'"
                                />
                                <button
                                    @click="removeProduct(product.id)"
                                    :disabled="products.length === 1"
                                    class="col-span-1 text-center text-lg leading-none text-gray-600 transition hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-30"
                                >×</button>
                            </div>

                            <!-- Subtotal -->
                            <div class="mt-3 flex items-center justify-end gap-3 border-t border-neutral-800 pt-3">
                                <span class="text-xs text-gray-500">Subtotal</span>
                                <span class="font-semibold tabular-nums text-white">{{ totalAmount.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2 · Customer Details ──────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-neutral-800 px-6 py-4">
                            <h2 class="font-semibold text-white">Customer Details</h2>
                        </div>

                        <div class="grid grid-cols-2 gap-4 px-6 py-5">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Full Name</label>
                                <input v-model="form.cus_name" type="text" :class="inputCls" :style="form.errors.cus_name ? 'border-color: #ef4444' : ''" />
                                <p v-if="form.errors.cus_name" class="mt-1 text-xs text-red-400">{{ form.errors.cus_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Email</label>
                                <input v-model="form.cus_email" type="email" :class="inputCls" :style="form.errors.cus_email ? 'border-color: #ef4444' : ''" />
                                <p v-if="form.errors.cus_email" class="mt-1 text-xs text-red-400">{{ form.errors.cus_email }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Phone</label>
                                <input v-model="form.cus_phone" type="text" :class="inputCls" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Country</label>
                                <input v-model="form.cus_country" type="text" :class="inputCls" />
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Address</label>
                                <input v-model="form.cus_add1" type="text" :class="inputCls" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">City</label>
                                <input v-model="form.cus_city" type="text" :class="inputCls" />
                            </div>
                        </div>
                    </div>

                    <!-- Section 3 · Optional References ──────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-neutral-800 px-6 py-4">
                            <h2 class="font-semibold text-white">Optional References</h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Attach to an existing plan, donation, or order. Leave blank to default to
                                <code class="rounded bg-gray-700 px-1 text-gray-300">0</code>.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-4 px-6 py-5">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Plan ID</label>
                                <input v-model.number="form.plan_id" type="number" min="1" placeholder="—" :class="inputCls" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Donation ID</label>
                                <input v-model.number="form.donation_id" type="number" min="1" placeholder="—" :class="inputCls" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-400">Order ID</label>
                                <input v-model.number="form.order_id" type="number" min="1" placeholder="—" :class="inputCls" />
                            </div>
                        </div>
                    </div>



                </div>
                <!-- ══ END LEFT COLUMN ════════════════════════════════════ -->

                <!-- ══ RIGHT COLUMN ══════════════════════════════════════════ -->
                <div class="col-span-2 space-y-4">
                    <!-- Section 4 · Gateway Selection ────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-neutral-800 px-6 py-4">
                            <h2 class="font-semibold text-white">Select Gateway</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Choose a gateway &amp; currency pair to calculate charges.</p>
                        </div>

                        <div class="px-6 py-5">
                            <p v-if="gateways.length === 0" class="text-sm text-gray-500">
                                No active gateways found.
                            </p>

                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="gw in gateways"
                                    :key="gw.id"
                                    @click="selectGateway(gw)"
                                    type="button"
                                    class="flex items-start gap-3 rounded-lg border p-3 text-left transition"
                                    :class="
                                        selectedGateway?.id === gw.id
                                            ? 'border-blue-600 bg-blue-950/40 ring-1 ring-blue-600'
                                            : 'border-gray-800 bg-neutral-900 hover:border-gray-600 hover:bg-gray-700/50'
                                    "
                                >
                                    <!-- Checkbox -->
                                    <span
                                        class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition"
                                        :class="
                                            selectedGateway?.id === gw.id
                                                ? 'border-blue-500 bg-blue-500'
                                                : 'border-gray-600'
                                        "
                                    >
                                        <svg v-if="selectedGateway?.id === gw.id" class="h-2.5 w-2.5 text-white" viewBox="0 0 10 8" fill="none">
                                            <path d="M1 4l3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <!-- Info -->
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-200">{{ gw.method_name }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500">{{ gw.currency }}</div>
                                        <div class="text-xs text-gray-600">{{ gw.min_amount }} – {{ gw.max_amount }}</div>
                                    </div>
                                </button>
                            </div>

                            <p v-if="form.errors.gateway"  class="mt-3 text-xs text-red-400">{{ form.errors.gateway }}</p>
                            <p v-if="form.errors.currency" class="mt-1 text-xs text-red-400">{{ form.errors.currency }}</p>
                            <p v-if="form.errors.amount"   class="mt-1 text-xs text-red-400">{{ form.errors.amount }}</p>
                        </div>
                    </div>
                    <!-- Live Payment Summary ──────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-neutral-800 px-5 py-4">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Live Payment Summary</h2>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="tabular-nums text-gray-300">{{ totalAmount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    Charges
                                    <span v-if="selectedGateway" class="text-gray-700">
                                        ({{ selectedGateway.fixed_charge }} + {{ selectedGateway.percent_charge }}%)
                                    </span>
                                </span>
                                <span class="tabular-nums text-gray-300">{{ charge.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-neutral-800 pt-3">
                                <span class="font-medium text-gray-400">Payable</span>
                                <span class="font-semibold tabular-nums text-white">{{ payable.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    Converted
                                    <span v-if="selectedGateway" class="text-gray-700">(× {{ selectedGateway.rate }})</span>
                                </span>
                                <span class="font-semibold tabular-nums text-emerald-400">
                                    {{ selectedGateway?.currency ?? '—' }} {{ finalAmount.toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Validation errors ────────────────────────────────── -->
                    <div
                        v-if="Object.keys(form.errors).length"
                        class="rounded-lg border border-red-900 bg-red-950 px-4 py-3"
                    >
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-red-400">Validation Errors</p>
                        <ul class="list-inside list-disc space-y-0.5 text-xs text-red-400">
                            <li v-for="(error, field) in form.errors" :key="field">
                                <span class="font-medium">{{ field }}:</span> {{ error }}
                            </li>
                        </ul>
                    </div>

                    <!-- Actions ──────────────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950 px-5 py-4">
                        <div class="flex flex-col gap-2">
                            <button
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full rounded-lg bg-slate-700 px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Sending...' : 'Send to Backend →' }}
                            </button>
                            <button
                                @click="router.visit(route('dashboard'))"
                                type="button"
                                class="w-full rounded-lg border border-neutral-800 px-5 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:bg-gray-800 hover:text-gray-200"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Result Inspector ─────────────────────────────────── -->
                    <div v-if="result" class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="flex items-center justify-between border-b border-neutral-800 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Backend Response</h2>
                            <span class="rounded-lg border border-emerald-800 bg-emerald-950 px-2.5 py-0.5 text-xs font-medium text-emerald-400">
                                ✓ Received
                            </span>
                        </div>
                        <pre class="overflow-x-auto rounded-b-lg bg-gray-950 p-5 text-xs leading-relaxed text-emerald-400">{{ resultJson }}</pre>
                    </div>

                </div>
                <!-- ══ END RIGHT COLUMN ══════════════════════════════════ -->

            </div>
        </div>
    </AppLayout>
</template>
