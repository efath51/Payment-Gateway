<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface ParameterField {
    key: string;
    title: string;
    value: string;
    global: boolean;
}

interface GatewayData {
    id?: number;
    name: string;
    alias: string;
    code: string;
    image: string | null;
    parameters: Record<string, { title: string; value: string; global: boolean }>;
    supported_currencies: Record<string, string>;
    description: string | null;
    status: number;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{ gateway?: GatewayData }>();

const isEdit = computed(() => !!props.gateway?.id);

// ─── Parameter builder ────────────────────────────────────────────────────────
// Each row = one JSON key in the `parameters` column

const toRows = (raw: GatewayData['parameters'] = {}): ParameterField[] =>
    Object.entries(raw).map(([key, v]) => ({ key, title: v.title, value: v.value, global: v.global }));

const params = ref<ParameterField[]>(
    props.gateway ? toRows(props.gateway.parameters)
                  : [{ key: '', title: '', value: '', global: true }],
);

const addParam    = () => params.value.push({ key: '', title: '', value: '', global: true });
const removeParam = (i: number) => params.value.splice(i, 1);

// Auto-fill key when user finishes typing the title ("Store ID" → "store_id")
const syncKey = (p: ParameterField) => {
    if (!p.key) p.key = p.title.toLowerCase().replace(/\s+/g, '_');
};

// Computed JSON preview — only includes rows that have a non-empty key
const parametersJson = computed(() => {
    const out: GatewayData['parameters'] = {};
    for (const p of params.value) {
        if (p.key.trim()) out[p.key.trim()] = { title: p.title, value: p.value, global: p.global };
    }
    return out;
});

// ─── Supported currencies ─────────────────────────────────────────────────────

const ALL_CURRENCIES = ['USD', 'EUR', 'GBP', 'BDT', 'INR', 'SGD', 'MYR', 'AUD', 'CAD', 'JPY', 'THB', 'PKR', 'IDR', 'PHP'];

const chosen = ref<Set<string>>(new Set(Object.keys(props.gateway?.supported_currencies ?? {})));

const toggleCurrency = (c: string) => {
    const s = new Set(chosen.value);
    s.has(c) ? s.delete(c) : s.add(c);
    chosen.value = s;
};

// ─── Image ────────────────────────────────────────────────────────────────────

const imagePreview = ref<string | null>(
    props.gateway?.image ? `/storage/gateways/${props.gateway.image}` : null,
);

const onImageChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
};

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
    name:                 props.gateway?.name        ?? '',
    alias:                props.gateway?.alias       ?? '',
    code:                 props.gateway?.code        ?? '',
    description:          props.gateway?.description ?? '',
    status:               props.gateway?.status      ?? 1,
    image:                null as File | null,
    parameters:           '',   // JSON string — filled on submit
    supported_currencies: '',   // JSON string — filled on submit
});

const submit = () => {
    // Serialize dynamic fields before POST
    form.parameters           = JSON.stringify(parametersJson.value);
    form.supported_currencies = JSON.stringify(Object.fromEntries([...chosen.value].map(c => [c, c])));

    if (isEdit.value) {
        // POST + _method spoofing so the file upload works over PUT
        form.transform(d => ({ ...d, _method: 'PUT' }))
            .post(route('admin.gateways.update', props.gateway!.id));
    } else {
        form.post(route('admin.gateways.store'));
    }
};

// ─── Meta ─────────────────────────────────────────────────────────────────────

const breadcrumbs = [
    { title: 'Gateways', href: route('admin.gateways.index') },
    { title: isEdit.value ? 'Edit' : 'Create', href: '#' },
];

// ─── Reusable classes (keep templates clean) ──────────────────────────────────

const input = 'w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2.5 text-sm text-gray-200 placeholder-gray-600 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
const label = 'mb-1.5 block text-xs font-medium text-gray-400';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full px-4 py-10">

            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">
                        {{ isEdit ? 'Edit Gateway' : 'Create Gateway' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ isEdit ? 'Update gateway config and credentials.' : 'Register a new payment gateway.' }}
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

                    <!-- Basic Info ──────────────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-6 py-4">
                            <h2 class="font-semibold text-white">Basic Information</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-4 px-6 py-5">

                            <div>
                                <label :class="label">Name</label>
                                <input v-model="form.name" type="text" placeholder="e.g. SSLCommerz" :class="input" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label :class="label">Alias</label>
                                <input v-model="form.alias" type="text" placeholder="e.g. SslCommerz" :class="input" />
                                <p v-if="form.errors.alias" class="mt-1 text-xs text-red-400">{{ form.errors.alias }}</p>
                            </div>

                            <div class="col-span-2">
                                <label :class="label">
                                    Code
                                    <span class="ml-1 text-gray-600">— used to dispatch to the processor class</span>
                                </label>
                                <input v-model="form.code" type="text" placeholder="e.g. sslcommerz" :class="input + ' font-mono'" />
                                <p v-if="form.errors.code" class="mt-1 text-xs text-red-400">{{ form.errors.code }}</p>
                            </div>

                            <div class="col-span-2">
                                <label :class="label">Description <span class="text-gray-600">(optional)</span></label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="What this gateway is for..."
                                    :class="input + ' resize-none'"
                                />
                            </div>

                        </div>
                    </div>

                    <!-- Parameters Builder ───────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="flex items-start justify-between border-b border-gray-700 px-6 py-4">
                            <div>
                                <h2 class="font-semibold text-white">Gateway Parameters</h2>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Credentials saved as JSON. Each row = one key in the
                                    <code class="rounded bg-gray-700 px-1 text-gray-300">parameters</code> column.
                                </p>
                            </div>
                            <button
                                @click="addParam"
                                class="shrink-0 rounded-lg border border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-400 transition hover:border-gray-500 hover:bg-gray-700 hover:text-gray-200"
                            >
                                + Add Field
                            </button>
                        </div>

                        <div class="px-6 py-5">
                            <!-- Column headers -->
                            <div class="mb-2 grid grid-cols-12 gap-3 text-xs font-medium uppercase tracking-wider text-gray-600">
                                <span class="col-span-3">Key</span>
                                <span class="col-span-3">Label / Title</span>
                                <span class="col-span-4">Value</span>
                                <span class="col-span-1 text-center">Global</span>
                                <span class="col-span-1"></span>
                            </div>

                            <!-- Rows -->
                            <div
                                v-for="(p, i) in params"
                                :key="i"
                                class="mb-2 grid grid-cols-12 items-center gap-3"
                            >
                                <input
                                    v-model="p.key"
                                    type="text"
                                    placeholder="store_id"
                                    :class="input + ' col-span-3 font-mono'"
                                />
                                <input
                                    v-model="p.title"
                                    type="text"
                                    placeholder="Store ID"
                                    :class="input + ' col-span-3'"
                                    @blur="syncKey(p)"
                                />
                                <input
                                    v-model="p.value"
                                    type="text"
                                    placeholder="sk_live_..."
                                    :class="input + ' col-span-4'"
                                />

                                <!-- Global toggle -->
                                <div class="col-span-1 flex justify-center">
                                    <button
                                        type="button"
                                        @click="p.global = !p.global"
                                        :title="p.global ? 'Global: on' : 'Global: off'"
                                        class="relative h-5 w-9 rounded-full transition-colors"
                                        :class="p.global ? 'bg-blue-600' : 'bg-gray-700'"
                                    >
                                        <span
                                            class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all"
                                            :class="p.global ? 'left-[18px]' : 'left-0.5'"
                                        />
                                    </button>
                                </div>

                                <button
                                    @click="removeParam(i)"
                                    :disabled="params.length === 1"
                                    class="col-span-1 text-center text-lg text-gray-600 transition hover:text-red-400 disabled:opacity-30"
                                >×</button>
                            </div>

                            <!-- Live JSON preview -->
                            <div class="mt-4 border-t border-gray-700 pt-4">
                                <p class="mb-2 text-xs text-gray-600">↓ Preview — what gets saved to the database</p>
                                <pre class="overflow-x-auto rounded-lg bg-gray-950 p-3 text-xs leading-relaxed text-emerald-400">{{ JSON.stringify(parametersJson, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- ══ END LEFT ══════════════════════════════════════════════ -->

                <!-- ══ RIGHT col-span-2 ══════════════════════════════════════ -->
                <div class="col-span-2 space-y-4">

                    <!-- Image upload ─────────────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Logo / Image</h2>
                        </div>
                        <div class="px-5 py-4">
                            <!-- Preview box -->
                            <div class="mb-3 flex h-20 items-center justify-center rounded-lg border border-gray-700 bg-gray-900">
                                <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="max-h-16 object-contain" />
                                <span v-else class="text-xs text-gray-600">No image</span>
                            </div>
                            <!-- File picker -->
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-gray-600 py-2.5 text-xs font-medium text-gray-500 transition hover:border-gray-500 hover:text-gray-300">
                                📁 Choose file
                                <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                            </label>
                            <p v-if="form.errors.image" class="mt-1 text-xs text-red-400">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <!-- Supported currencies ─────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Supported Currencies</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Select all currencies this gateway accepts.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 px-5 py-4">
                            <button
                                v-for="c in ALL_CURRENCIES"
                                :key="c"
                                type="button"
                                @click="toggleCurrency(c)"
                                class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    chosen.has(c)
                                        ? 'border-blue-600 bg-blue-950/40 text-blue-400'
                                        : 'border-gray-700 text-gray-500 hover:border-gray-600 hover:text-gray-300'
                                "
                            >{{ c }}</button>
                        </div>
                        <p v-if="chosen.size === 0" class="px-5 pb-3 text-xs text-amber-500">⚠ Select at least one currency.</p>
                    </div>

                    <!-- Status ───────────────────────────────────────────────── -->
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950">
                        <div class="border-b border-gray-700 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Status</h2>
                        </div>
                        <div class="flex items-center justify-between px-5 py-4">
                            <div>
                                <p class="text-sm font-medium" :class="form.status === 1 ? 'text-emerald-400' : 'text-gray-400'">
                                    {{ form.status === 1 ? 'Active' : 'Disabled' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ form.status === 1 ? 'Visible and usable by users.' : 'Hidden from all users.' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="form.status = form.status === 1 ? 0 : 1"
                                class="relative h-6 w-11 rounded-full transition-colors"
                                :class="form.status === 1 ? 'bg-blue-600' : 'bg-gray-700'"
                            >
                                <span
                                    class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition-all"
                                    :class="form.status === 1 ? 'left-6' : 'left-1'"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Actions ──────────────────────────────────────────────── -->
                    <div class="rounded-lg border border-gray-700 bg-gray-900 px-5 py-4">
                        <div class="flex flex-col gap-2">
                            <button
                                @click="submit"
                                :disabled="form.processing"
                                class="w-full rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Create Gateway') }}
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
