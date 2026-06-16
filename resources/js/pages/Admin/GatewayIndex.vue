<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Gateway {
    id: number;
    name: string;
    alias: string;
    code: string;
    image: string | null;
    status: number;
    currencies_count: number;
    supported_currencies: Record<string, string>;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{ gateways: Gateway[] }>();

// ─── Filters ─────────────────────────────────────────────────────────────────

const search       = ref('');
const statusFilter = ref('all'); // 'all' | 'active' | 'inactive'

const filtered = computed(() =>
    props.gateways.filter((g) => {
        const q = search.value.trim().toLowerCase();

        const matchSearch =
            !q ||
            g.name.toLowerCase().includes(q)  ||
            g.alias.toLowerCase().includes(q) ||
            g.code.toLowerCase().includes(q);

        const matchStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active'   && g.status === 1) ||
            (statusFilter.value === 'inactive' && g.status === 0);

        return matchSearch && matchStatus;
    }),
);

const hasActiveFilter = computed(
    () => search.value.trim() !== '' || statusFilter.value !== 'all',
);

const clearFilters = () => {
    search.value       = '';
    statusFilter.value = 'all';
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

// Show up to 4 currency chips, then "+N" overflow
const visibleCurrencies = (g: Gateway) => Object.keys(g.supported_currencies).slice(0, 4);
const overflowCount     = (g: Gateway) => Math.max(0, Object.keys(g.supported_currencies).length - 4);

// Avatar color based on first letter (cycles through a palette)
const avatarColors: Record<string, string> = {
    A: 'bg-violet-500/20 text-violet-400',
    B: 'bg-blue-500/20 text-blue-400',
    C: 'bg-cyan-500/20 text-cyan-400',
    D: 'bg-indigo-500/20 text-indigo-400',
    E: 'bg-emerald-500/20 text-emerald-400',
    F: 'bg-amber-500/20 text-amber-400',
    G: 'bg-rose-500/20 text-rose-400',
    P: 'bg-pink-500/20 text-pink-400',
    R: 'bg-orange-500/20 text-orange-400',
    S: 'bg-sky-500/20 text-sky-400',
    T: 'bg-teal-500/20 text-teal-400',
    U: 'bg-purple-500/20 text-purple-400',
};

const avatarColor = (name: string) =>
    avatarColors[name.charAt(0).toUpperCase()] ?? 'bg-neutral-700 text-neutral-400 dark:bg-neutral-700';

// ─── Actions ─────────────────────────────────────────────────────────────────

const toggleStatus = (id: number) =>
    router.patch(route('admin.gateways.toggle', id), {}, { preserveScroll: true });

// ─── Meta ─────────────────────────────────────────────────────────────────────

const breadcrumbs = [{ title: 'Gateways', href: route('admin.gateways.index') }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-10">

            <!-- ── Page header ───────────────────────────────────────────── -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Gateways
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-500">
                        Manage payment gateways and their currency configurations.
                    </p>
                </div>

                <button
                    @click="router.visit(route('admin.gateways.create'))"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50
                           dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    + Add Gateway
                </button>
            </div>

            <!-- ── Main card ─────────────────────────────────────────────── -->
            <div class="rounded-lg border border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-950">

                <!-- Filter bar ────────────────────────────────────────────── -->
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 p-4 dark:border-neutral-800">

                    <!-- Search input -->
                    <div class="relative min-w-[220px] flex-1">
                        <!-- magnifying glass — matches Users page exactly -->
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-neutral-500"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search gateways..."
                            class="w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 outline-none transition
                                   focus:border-blue-500 focus:ring-1 focus:ring-blue-500
                                   dark:border-neutral-700 dark:text-neutral-200 dark:placeholder-neutral-500 dark:focus:border-blue-500"
                        />
                    </div>

                    <!-- Status filter — "Status: [dropdown]" pattern from image -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-neutral-500">Status:</span>
                        <div class="relative">
                            <select
                                v-model="statusFilter"
                                class="appearance-none rounded-lg border border-gray-300 bg-transparent py-2 pl-3 pr-8 text-sm text-gray-700 outline-none transition
                                       focus:border-blue-500 focus:ring-1 focus:ring-blue-500
                                       dark:border-neutral-700 dark:text-neutral-300"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <!-- chevron -->
                            <svg class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400 dark:text-neutral-500"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter button (matches image style — outlined) -->
                    <button
                        class="rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50
                               dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >
                        Filter
                    </button>

                    <!-- Clear (only shown when a filter is active) -->
                    <button
                        v-if="hasActiveFilter"
                        @click="clearFilters"
                        class="text-xs text-gray-400 underline-offset-2 transition hover:text-gray-600 hover:underline dark:text-neutral-600 dark:hover:text-neutral-400"
                    >
                        Clear
                    </button>

                </div>
                <!-- End filter bar -->

                <!-- Table ─────────────────────────────────────────────────── -->
                <table class="w-full">

                    <!-- Column headers — same subtle uppercase style as image -->
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/70 dark:border-neutral-800 dark:bg-neutral-900/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Gateway
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Code
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Currencies
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Supports
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-neutral-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Empty state -->
                        <tr v-if="filtered.length === 0">
                            <td colspan="6" class="px-6 py-14 text-center">
                                <p class="text-sm text-gray-400 dark:text-neutral-600">
                                    No gateways match your filters.
                                </p>
                                <button
                                    v-if="hasActiveFilter"
                                    @click="clearFilters"
                                    class="mt-2 text-xs text-blue-500 hover:underline dark:text-blue-400"
                                >
                                    Clear filters
                                </button>
                            </td>
                        </tr>

                        <!-- Gateway rows -->
                        <tr
                            v-for="gw in filtered"
                            :key="gw.id"
                            class="border-b border-gray-100 transition last:border-0
                                   hover:bg-gray-50 dark:border-neutral-800/60 dark:hover:bg-neutral-900/50"
                        >

                            <!-- Gateway name + image — same avatar circle pattern as Users page -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <!-- Logo or letter avatar -->
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-neutral-700 dark:bg-neutral-800"
                                        :class="!gw.image ? avatarColor(gw.name) : ''"
                                    >
                                        <img
                                            v-if="gw.image"
                                            :src="gw.image"
                                            :alt="gw.name"
                                            class="h-full w-full object-contain p-1.5"
                                        />
                                        <span v-else class="text-sm font-semibold">
                                            {{ gw.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>

                                    <!-- Name + alias (mirrors name + email from Users) -->
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-neutral-100">
                                            {{ gw.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-neutral-500">
                                            {{ gw.alias }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Code — pill badge, mirrors "Roles" badge in image -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-2.5 py-1 font-mono text-xs text-gray-600
                                             dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400">
                                    {{ gw.code }}
                                </span>
                            </td>

                            <!-- Currency count — small pill -->
                            <td class="px-6 py-4">
                                <button
                                    @click="router.visit(route('admin.gateway-currencies.create', { gateway_id: gw.id }))"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600
                                           dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:border-blue-700 dark:hover:bg-blue-950/30 dark:hover:text-blue-400"
                                    :title="`${gw.currencies_count} configured — click to add another`"
                                >
                                    {{ gw.currencies_count }}
                                    <span class="text-gray-400 dark:text-neutral-600">
                                        {{ gw.currencies_count === 1 ? 'currency' : 'currencies' }}
                                    </span>
                                </button>
                            </td>

                            <!-- Supported currencies — chips, max 4 then +N -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span
                                        v-for="c in visibleCurrencies(gw)"
                                        :key="c"
                                        class="rounded px-1.5 py-0.5 text-xs text-gray-500 ring-1 ring-inset ring-gray-200 dark:text-neutral-500 dark:ring-neutral-700"
                                    >
                                        {{ c }}
                                    </span>
                                    <span
                                        v-if="overflowCount(gw) > 0"
                                        class="rounded px-1.5 py-0.5 text-xs text-gray-400 dark:text-neutral-600"
                                    >
                                        +{{ overflowCount(gw) }}
                                    </span>
                                    <span
                                        v-if="Object.keys(gw.supported_currencies).length === 0"
                                        class="text-xs text-gray-300 dark:text-neutral-700"
                                    >—</span>
                                </div>
                            </td>

                            <!-- Status badge — exact green "Active" style from image -->
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        gw.status === 1
                                            ? 'bg-emerald-500/10 text-emerald-600 ring-1 ring-inset ring-emerald-500/20 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200 dark:bg-neutral-800 dark:text-neutral-500 dark:ring-neutral-700'
                                    "
                                >
                                    <!-- Status dot -->
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="gw.status === 1 ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-neutral-600'"
                                    />
                                    {{ gw.status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Actions — icon buttons, same style as edit/clock/trash in image -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">

                                    <!-- Edit gateway -->
                                    <button
                                        @click="router.visit(route('admin.gateways.edit', gw.id))"
                                        title="Edit gateway"
                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700
                                               dark:text-neutral-500 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                                    >
                                        <!-- Pencil icon -->
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Add / manage currencies -->
                                    <button
                                        @click="router.visit(route('admin.gateway-currencies.create', { gateway_id: gw.id }))"
                                        title="Add currency"
                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700
                                               dark:text-neutral-500 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                                    >
                                        <!-- Plus-circle icon -->
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <!-- Toggle status (power icon — matches clock icon position in image) -->
                                    <button
                                        @click="toggleStatus(gw.id)"
                                        :title="gw.status === 1 ? 'Disable gateway' : 'Enable gateway'"
                                        class="rounded-lg p-1.5 transition"
                                        :class="
                                            gw.status === 1
                                                ? 'text-emerald-500 hover:bg-emerald-500/10 dark:hover:bg-emerald-500/10'
                                                : 'text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-400'
                                        "
                                    >
                                        <!-- Power icon -->
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                                        </svg>
                                    </button>

                                </div>
                            </td>

                        </tr>

                    </tbody>
                </table>

                <!-- Footer — result count -->
                <div
                    v-if="gateways.length > 0"
                    class="border-t border-gray-100 px-6 py-3 dark:border-neutral-800"
                >
                    <p class="text-xs text-gray-400 dark:text-neutral-600">
                        Showing
                        <span class="font-medium text-gray-600 dark:text-neutral-400">{{ filtered.length }}</span>
                        of
                        <span class="font-medium text-gray-600 dark:text-neutral-400">{{ gateways.length }}</span>
                        gateways
                        <span v-if="hasActiveFilter"> — filtered</span>
                    </p>
                </div>

            </div>
            <!-- End main card -->

        </div>
    </AppLayout>
</template>
