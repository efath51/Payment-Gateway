<script setup lang="ts">
import { computed, ref, reactive, watch } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Product {
    id: number;
    name: string;
    image: string;
    price: number;
    quantity: number;
    inCart: boolean;
}

interface Gateway {
    id: number;
    name: string;
    code: string;
    currency: string;
    logo: string;
    fixed_charge: number;
    percent_charge: number;
    rate: number;
    min_amount: number;
    max_amount: number;
}

type Step = 'cart' | 'pin' | 'processing' | 'result';

// ─── Demo Products ────────────────────────────────────────────────────────────

const products = reactive<Product[]>([
    {
        id: 1,
        name: 'Premium Wireless Headphones',
        image: '🎧',
        price: 2500,
        quantity: 1,
        inCart: true,
    },
    {
        id: 2,
        name: 'Smart Fitness Band Pro',
        image: '⌚',
        price: 1800,
        quantity: 1,
        inCart: true,
    },
    {
        id: 3,
        name: 'Portable Bluetooth Speaker',
        image: '🔊',
        price: 3200,
        quantity: 1,
        inCart: false,
    },
    {
        id: 4,
        name: 'USB-C Fast Charging Cable',
        image: '🔌',
        price: 450,
        quantity: 2,
        inCart: true,
    },
]);

// ─── Demo Gateways ────────────────────────────────────────────────────────────

const gateways: Gateway[] = [
    {
        id: 1,
        name: 'bKash',
        code: 'bkash',
        currency: 'BDT',
        logo: '🟪',
        fixed_charge: 5,
        percent_charge: 1.5,
        rate: 1,
        min_amount: 10,
        max_amount: 50000,
    },
    {
        id: 2,
        name: 'Nagad',
        code: 'nagad',
        currency: 'BDT',
        logo: '🟧',
        fixed_charge: 0,
        percent_charge: 1.0,
        rate: 1,
        min_amount: 10,
        max_amount: 25000,
    },
    {
        id: 3,
        name: 'Rocket',
        code: 'rocket',
        currency: 'BDT',
        logo: '🟣',
        fixed_charge: 10,
        percent_charge: 0.8,
        rate: 1,
        min_amount: 50,
        max_amount: 30000,
    },
    {
        id: 4,
        name: 'SSLCommerz',
        code: 'sslcommerz',
        currency: 'BDT',
        logo: '🟦',
        fixed_charge: 0,
        percent_charge: 2.0,
        rate: 1,
        min_amount: 100,
        max_amount: 100000,
    },
];

// ─── State ────────────────────────────────────────────────────────────────────

const selectedGateway = ref<Gateway | null>(null);
const currentStep     = ref<Step>('cart');
const pinDigits       = ref(['', '', '', '', '']);
const pinError        = ref('');
const paymentSuccess  = ref(true);
const trxId           = ref('');

// ─── Cart helpers ─────────────────────────────────────────────────────────────

const cartItems = computed(() => products.filter(p => p.inCart));

const toggleCart = (product: Product) => {
    product.inCart = !product.inCart;
    if (!product.inCart) product.quantity = 1;
};

const updateQty = (product: Product, delta: number) => {
    const next = product.quantity + delta;
    if (next >= 1 && next <= 10) product.quantity = next;
};

// ─── Computed Summary ─────────────────────────────────────────────────────────

const subtotal = computed(() =>
    cartItems.value.reduce((sum, p) => sum + p.price * p.quantity, 0),
);

const charge = computed(() => {
    if (!selectedGateway.value) return 0;
    const g = selectedGateway.value;
    return g.fixed_charge + (subtotal.value * g.percent_charge) / 100;
});

const totalPayable = computed(() => subtotal.value + charge.value);

const canProceed = computed(
    () => cartItems.value.length > 0 && selectedGateway.value !== null,
);

// ─── Gateway selection ────────────────────────────────────────────────────────

const selectGateway = (gw: Gateway) => {
    selectedGateway.value = selectedGateway.value?.id === gw.id ? null : gw;
};

// ─── PIN handling ─────────────────────────────────────────────────────────────

const pinInputRefs = ref<(HTMLInputElement | null)[]>([]);

const setPinRef = (el: any, i: number) => {
    pinInputRefs.value[i] = el as HTMLInputElement;
};

const onPinInput = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const val = input.value.replace(/\D/g, '');
    pinDigits.value[index] = val.slice(-1);

    if (val && index < 4) {
        pinInputRefs.value[index + 1]?.focus();
    }
};

const onPinKeydown = (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !pinDigits.value[index] && index > 0) {
        pinInputRefs.value[index - 1]?.focus();
    }
};

const fullPin = computed(() => pinDigits.value.join(''));

// ─── Flow actions ─────────────────────────────────────────────────────────────

const continueToPayment = () => {
    pinDigits.value = ['', '', '', '', ''];
    pinError.value = '';
    currentStep.value = 'pin';
};

// const submit = () => {
//     form.amount   = totalAmount.value;
//     form.products = products.value;
//     form.post(route('dev.deposit.store'), { preserveScroll: true });
// };

const confirmPin = () => {
    if (fullPin.value.length < 5) {
        pinError.value = 'Please enter your full 5-digit PIN.';
        return;
    }

    pinError.value = '';
    currentStep.value = 'processing';

    // Simulate backend call (1.5s delay)
    setTimeout(() => {
        // Demo: PIN "00000" = failure, anything else = success
        paymentSuccess.value = fullPin.value !== '00000';
        trxId.value = 'TXN' + Date.now().toString(36).toUpperCase() + Math.random().toString(36).slice(2, 6).toUpperCase();
        currentStep.value = 'result';
    }, 2000);
};

const goBackToCart = () => {
    currentStep.value = 'cart';
    pinDigits.value = ['', '', '', '', ''];
    pinError.value = '';
};

const startNewOrder = () => {
    currentStep.value = 'cart';
    selectedGateway.value = null;
    pinDigits.value = ['', '', '', '', ''];
    pinError.value = '';
    products.forEach(p => {
        p.quantity = 1;
        p.inCart = p.id <= 2 || p.id === 4;
    });
};

// ─── Shared CSS classes ───────────────────────────────────────────────────────

const cardCls = 'rounded-lg border border-neutral-800 bg-neutral-950';
const headCls = 'border-b border-neutral-800 px-6 py-4';
</script>

<template>
    <div class="min-h-screen bg-neutral-950 text-gray-200">

        <!-- ── Top nav bar ──────────────────────────────────────────────── -->
        <header class="border-b border-neutral-800 bg-neutral-950/80 backdrop-blur-sm sticky top-0 z-30">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">G</span>
                    <span class="text-lg font-semibold text-white">Gateway Store</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="relative text-gray-400 transition hover:text-gray-200 cursor-pointer">
                        <!-- Cart icon -->
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <span v-if="cartItems.length"
                              class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                            {{ cartItems.length }}
                        </span>
                    </span>
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-800 text-xs font-semibold text-gray-300">KH</div>
                        <span class="text-sm text-gray-400">Customer</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MAIN CONTENT                                                    -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <main class="mx-auto max-w-6xl px-6 py-8">

            <!-- Step indicator -->
            <div class="mb-8 flex items-center justify-center gap-2">
                <div v-for="(s, i) in ['Cart & Gateway', 'Payment', 'Result']" :key="s" class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-colors"
                          :class="
                              (i === 0 && currentStep === 'cart')      ? 'bg-blue-600 text-white' :
                              (i === 1 && (currentStep === 'pin' || currentStep === 'processing')) ? 'bg-blue-600 text-white' :
                              (i === 2 && currentStep === 'result')    ? 'bg-blue-600 text-white' :
                              'bg-neutral-800 text-gray-600'
                          "
                    >{{ i + 1 }}</span>
                    <span class="text-xs font-medium"
                          :class="
                              (i === 0 && currentStep === 'cart')      ? 'text-white' :
                              (i === 1 && (currentStep === 'pin' || currentStep === 'processing')) ? 'text-white' :
                              (i === 2 && currentStep === 'result')    ? 'text-white' :
                              'text-gray-600'
                          "
                    >{{ s }}</span>
                    <div v-if="i < 2" class="mx-2 h-px w-12 bg-neutral-800" />
                </div>
            </div>

            <!-- ──────────────────────────────────────────────────────────── -->
            <!-- STEP 1: CART + GATEWAY                                       -->
            <!-- ──────────────────────────────────────────────────────────── -->
            <div v-if="currentStep === 'cart'" class="grid grid-cols-5 gap-5">

                <!-- ══ LEFT COLUMN (col-span-3) ══════════════════════════ -->
                <div class="col-span-3 space-y-5">

                    <!-- Product Selection ─────────────────────────────── -->
                    <div :class="cardCls">
                        <div :class="headCls" class="flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold text-white">Your Cart</h2>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ cartItems.length }} item{{ cartItems.length !== 1 ? 's' : '' }} selected
                                </p>
                            </div>
                        </div>

                        <div class="divide-y divide-neutral-800">
                            <div
                                v-for="product in products"
                                :key="product.id"
                                class="flex items-center gap-4 px-6 py-4 transition"
                                :class="product.inCart ? 'opacity-100' : 'opacity-40'"
                            >
                                <!-- Product image / emoji -->
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-neutral-800 bg-neutral-900 text-2xl">
                                    {{ product.image }}
                                </div>

                                <!-- Info -->
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-100 truncate">{{ product.name }}</p>
                                    <p class="text-xs text-gray-500">৳{{ product.price.toLocaleString() }} each</p>
                                </div>

                                <!-- Qty controls -->
                                <div v-if="product.inCart" class="flex items-center gap-1.5">
                                    <button
                                        @click="updateQty(product, -1)"
                                        :disabled="product.quantity <= 1"
                                        class="flex h-7 w-7 items-center justify-center rounded-md border border-neutral-700 bg-neutral-900 text-xs text-gray-400 transition hover:border-gray-600 hover:text-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                                    >−</button>
                                    <span class="w-8 text-center text-sm tabular-nums text-white">{{ product.quantity }}</span>
                                    <button
                                        @click="updateQty(product, 1)"
                                        :disabled="product.quantity >= 10"
                                        class="flex h-7 w-7 items-center justify-center rounded-md border border-neutral-700 bg-neutral-900 text-xs text-gray-400 transition hover:border-gray-600 hover:text-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                                    >+</button>
                                </div>

                                <!-- Line total / toggle -->
                                <div class="flex items-center gap-3">
                                    <span v-if="product.inCart" class="w-20 text-right text-sm font-semibold tabular-nums text-white">
                                        ৳{{ (product.price * product.quantity).toLocaleString() }}
                                    </span>
                                    <button
                                        @click="toggleCart(product)"
                                        class="relative h-5 w-9 rounded-full transition-colors"
                                        :class="product.inCart ? 'bg-blue-600' : 'bg-neutral-700'"
                                    >
                                        <span
                                            class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all"
                                            :class="product.inCart ? 'left-[18px]' : 'left-0.5'"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cart subtotal -->
                        <div class="flex items-center justify-between border-t border-neutral-800 px-6 py-4">
                            <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Subtotal</span>
                            <span class="text-lg font-semibold tabular-nums text-white">৳{{ subtotal.toLocaleString() }}</span>
                        </div>
                    </div>

                    <!-- Gateway Selection ─────────────────────────────── -->
                    <div :class="cardCls">
                        <div :class="headCls">
                            <h2 class="font-semibold text-white">Select Payment Method</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Choose how you'd like to pay.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 px-6 py-5">
                            <button
                                v-for="gw in gateways"
                                :key="gw.id"
                                @click="selectGateway(gw)"
                                type="button"
                                class="flex items-center gap-3 rounded-lg border p-4 text-left transition"
                                :class="
                                    selectedGateway?.id === gw.id
                                        ? 'border-blue-600 bg-blue-950/40 ring-1 ring-blue-600'
                                        : 'border-neutral-800 bg-neutral-900 hover:border-gray-600 hover:bg-neutral-800'
                                "
                            >
                                <!-- Radio circle -->
                                <span
                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition"
                                    :class="
                                        selectedGateway?.id === gw.id
                                            ? 'border-blue-500'
                                            : 'border-gray-600'
                                    "
                                >
                                    <span
                                        v-if="selectedGateway?.id === gw.id"
                                        class="h-2 w-2 rounded-full bg-blue-500"
                                    />
                                </span>

                                <!-- Logo -->
                                <span class="text-2xl">{{ gw.logo }}</span>

                                <!-- Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-200">{{ gw.name }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ gw.currency }} · Fee: ৳{{ gw.fixed_charge }}
                                        <span v-if="gw.percent_charge"> + {{ gw.percent_charge }}%</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                </div>
                <!-- ══ END LEFT COLUMN ════════════════════════════════════ -->

                <!-- ══ RIGHT COLUMN (col-span-2) ══════════════════════════ -->
                <div class="col-span-2 space-y-5">

                    <!-- Payment Summary ──────────────────────────────── -->
                    <div :class="cardCls">
                        <div :class="headCls">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Payment Summary</h2>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm">
                            <!-- Item lines -->
                            <div v-for="item in cartItems" :key="item.id" class="flex justify-between">
                                <span class="text-gray-500 truncate max-w-[60%]">
                                    {{ item.name }}
                                    <span class="text-gray-700"> × {{ item.quantity }}</span>
                                </span>
                                <span class="tabular-nums text-gray-300">৳{{ (item.price * item.quantity).toLocaleString() }}</span>
                            </div>

                            <div class="border-t border-neutral-800 pt-3 flex justify-between">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="tabular-nums text-gray-300">৳{{ subtotal.toLocaleString() }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    Gateway Fee
                                    <span v-if="selectedGateway" class="text-gray-700 text-xs">
                                        (৳{{ selectedGateway.fixed_charge }} + {{ selectedGateway.percent_charge }}%)
                                    </span>
                                </span>
                                <span class="tabular-nums text-gray-300">৳{{ charge.toFixed(2) }}</span>
                            </div>

                            <div class="flex justify-between border-t border-neutral-800 pt-3">
                                <span class="font-medium text-gray-400">Total Payable</span>
                                <span class="text-lg font-semibold tabular-nums text-white">
                                    ৳{{ totalPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </span>
                            </div>

                            <div v-if="selectedGateway" class="flex justify-between">
                                <span class="text-gray-500">Pay via</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-800 bg-blue-950/40 px-2.5 py-0.5 text-xs font-medium text-blue-400">
                                    {{ selectedGateway.logo }} {{ selectedGateway.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Continue button ──────────────────────────────── -->
                    <div :class="cardCls + ' px-5 py-4'">
                        <button
                            @click="continueToPayment"
                            :disabled="!canProceed"
                            class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Continue to Payment →
                        </button>
                        <p v-if="!canProceed" class="mt-2 text-center text-xs text-gray-600">
                            Add items and select a gateway to proceed.
                        </p>
                    </div>

                    <!-- Order info ───────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-900/50 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-4 w-4 mt-0.5 shrink-0 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-xs text-gray-500 leading-relaxed">
                                <p>Your payment is protected with end-to-end encryption. Gateway fees are calculated in real-time based on your selected method.</p>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- ══ END RIGHT COLUMN ═══════════════════════════════════ -->

            </div>
            <!-- ══ END STEP 1 ══════════════════════════════════════════════ -->


            <!-- ──────────────────────────────────────────────────────────── -->
            <!-- STEP 2: PIN MODAL (overlay)                                  -->
            <!-- ──────────────────────────────────────────────────────────── -->
            <Teleport to="body">
                <Transition name="fade">
                    <div
                        v-if="currentStep === 'pin' || currentStep === 'processing'"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
                    >
                        <!-- PIN card -->
                        <div class="w-full max-w-sm rounded-xl border border-neutral-800 bg-neutral-950 shadow-2xl">

                            <!-- Header -->
                            <div class="border-b border-neutral-800 px-6 py-5 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-blue-600/10 text-3xl">
                                    {{ selectedGateway?.logo }}
                                </div>
                                <h3 class="text-lg font-semibold text-white">{{ selectedGateway?.name }} Payment</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Pay
                                    <span class="font-semibold text-white">
                                        ৳{{ totalPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </span>
                                </p>
                            </div>

                            <!-- Processing state -->
                            <div v-if="currentStep === 'processing'" class="px-6 py-10 text-center">
                                <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-2 border-neutral-700 border-t-blue-500"></div>
                                <p class="text-sm text-gray-400">Verifying payment…</p>
                                <p class="mt-1 text-xs text-gray-600">Please wait, do not close this window.</p>
                            </div>

                            <!-- PIN input -->
                            <div v-else class="px-6 py-6">
                                <label class="mb-4 block text-center text-xs font-medium text-gray-400">
                                    Enter your 5-digit {{ selectedGateway?.name }} PIN
                                </label>

                                <div class="flex justify-center gap-3 mb-4">
                                    <input
                                        v-for="(_, i) in pinDigits"
                                        :key="i"
                                        :ref="(el) => setPinRef(el, i)"
                                        type="password"
                                        inputmode="numeric"
                                        maxlength="1"
                                        :value="pinDigits[i]"
                                        @input="onPinInput(i, $event)"
                                        @keydown="onPinKeydown(i, $event)"
                                        class="h-12 w-12 rounded-lg border bg-neutral-900 text-center text-lg font-semibold text-white outline-none transition
                                               focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        :class="pinError ? 'border-red-500' : 'border-neutral-700'"
                                    />
                                </div>

                                <p v-if="pinError" class="mb-3 text-center text-xs text-red-400">{{ pinError }}</p>

                                <button
                                    @click="confirmPin"
                                    class="w-full rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-500"
                                >
                                    Confirm Payment
                                </button>

                                <button
                                    @click="goBackToCart"
                                    class="mt-2 w-full rounded-lg border border-neutral-800 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200"
                                >
                                    Cancel
                                </button>

                                <p class="mt-4 text-center text-[11px] text-gray-600">
                                    🔒 Secured by {{ selectedGateway?.name }}. Your PIN is never stored.
                                </p>
                            </div>

                        </div>
                    </div>
                </Transition>
            </Teleport>


            <!-- ──────────────────────────────────────────────────────────── -->
            <!-- STEP 3: RESULT                                               -->
            <!-- ──────────────────────────────────────────────────────────── -->
            <div v-if="currentStep === 'result'" class="mx-auto max-w-md">

                <!-- Success -->
                <div v-if="paymentSuccess" :class="cardCls" class="text-center overflow-hidden">
                    <!-- Gradient banner -->
                    <div class="h-2 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-500" />

                    <div class="px-8 py-10">
                        <!-- Check icon -->
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="h-10 w-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-white">Payment Successful</h2>
                        <p class="mt-2 text-sm text-gray-500">Your order has been placed successfully.</p>

                        <!-- Details -->
                        <div class="mt-8 space-y-3 rounded-lg border border-neutral-800 bg-neutral-900 p-5 text-left text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Transaction ID</span>
                                <span class="font-mono text-xs font-semibold text-emerald-400">{{ trxId }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Amount Paid</span>
                                <span class="tabular-nums text-white font-semibold">
                                    ৳{{ totalPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Gateway</span>
                                <span class="text-gray-300">{{ selectedGateway?.logo }} {{ selectedGateway?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Items</span>
                                <span class="text-gray-300">{{ cartItems.length }} product{{ cartItems.length !== 1 ? 's' : '' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    Completed
                                </span>
                            </div>
                        </div>

                        <button
                            @click="startNewOrder"
                            class="mt-6 w-full rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100"
                        >
                            Start New Order
                        </button>
                    </div>
                </div>

                <!-- Failure -->
                <div v-else :class="cardCls" class="text-center overflow-hidden">
                    <!-- Red gradient banner -->
                    <div class="h-2 bg-gradient-to-r from-red-600 via-red-500 to-orange-500" />

                    <div class="px-8 py-10">
                        <!-- X icon -->
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-red-500/10">
                            <svg class="h-10 w-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-white">Payment Failed</h2>
                        <p class="mt-2 text-sm text-gray-500">
                            Your payment could not be processed. Please check your PIN and try again.
                        </p>

                        <!-- Error details -->
                        <div class="mt-8 rounded-lg border border-red-900 bg-red-950/50 p-5 text-left text-sm">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-500">Reference</span>
                                <span class="font-mono text-xs text-red-400">{{ trxId }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-500">Gateway</span>
                                <span class="text-gray-400">{{ selectedGateway?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500" />
                                    Failed
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-2">
                            <button
                                @click="goBackToCart"
                                class="w-full rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100"
                            >
                                Try Again
                            </button>
                            <button
                                @click="startNewOrder"
                                class="w-full rounded-lg border border-neutral-800 px-5 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200"
                            >
                                Start New Order
                            </button>
                        </div>
                    </div>
                </div>

            </div>
            <!-- ══ END STEP 3 ══════════════════════════════════════════════ -->

        </main>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
