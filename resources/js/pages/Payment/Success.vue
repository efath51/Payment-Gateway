<!-- resources/js/Pages/Payment/Success.vue -->
<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    trx: String,
    amount: Number,
    message: String,
});

const showDetails = ref(true);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Payment Successful! 🎉
            </h1>

            <p class="text-gray-600 mb-6">
                {{ message || 'Your payment has been completed successfully.' }}
            </p>

            <!-- Payment Details -->
            <div v-if="showDetails" class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Transaction ID</span>
                    <span class="font-mono text-sm font-medium">{{ trx }}</span>
                </div>
                <div class="flex justify-between py-2" v-if="amount">
                    <span class="text-gray-600">Amount Paid</span>
                    <!-- <span class="font-semibold">${{ amount.toFixed(2) }}</span> -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <Link
                    :href="route('user.deposit.index')"
                    class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200"
                >
                    View Transaction History
                </Link>

                <Link
                    :href="route('home')"
                    class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition duration-200"
                >
                    Continue Shopping
                </Link>
            </div>
        </div>
    </div>
</template>
