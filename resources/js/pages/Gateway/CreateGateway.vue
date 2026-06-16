<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';

const props = defineProps<{
    gateways?: any[];
}>();

const gateways = ref(props.gateways || []);
const isEditing = ref(false);
const saving = ref(false);
const errors = ref({});

const form = useForm({
    id: null,
    name: '',
    alias: '',
    code: '',
    status: '1',
    image: null as File | null,
    image_preview: null as string | null,
    currencies: [] as any[],
    gateway_parameters: {} as Record<string, any>,   // ← Important
});

const fetchGateways = async () => {
    const res = await axios.get('/api/admin/gateways');
    gateways.value = res.data;
};

const editGateway = (gateway: any) => {
    isEditing.value = true;
    form.reset();

    form.id = gateway.id;
    form.name = gateway.name || '';
    form.alias = gateway.alias || '';
    form.code = gateway.code || '';
    form.status = gateway.status?.toString() || '1';
    form.image_preview = gateway.image ? `/storage/gateway/${gateway.image}` : null;

    // Handle Gateway Parameters
    if (gateway.gateway_parameters) {
        try {
            form.gateway_parameters = typeof gateway.gateway_parameters === 'string'
                ? JSON.parse(gateway.gateway_parameters)
                : gateway.gateway_parameters;
        } catch (e) {
            form.gateway_parameters = {};
        }
    } else {
        form.gateway_parameters = {};
    }

    // Currencies (if needed)
    form.currencies = gateway.currencies?.map((c: any) => ({ ...c })) || [];
};

const saveGateway = () => {
    saving.value = true;

    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('alias', form.alias);
    formData.append('code', form.code);
    formData.append('status', form.status);
    formData.append('gateway_parameters', JSON.stringify(form.gateway_parameters));
    formData.append('currencies', JSON.stringify(form.currencies));

    if (form.image) formData.append('image', form.image);

    const url = form.id ? `/api/admin/gateways/${form.id}` : '/api/admin/gateways';

    router.post(url, formData, {
        onSuccess: () => {
            alert('Gateway saved successfully!');
            isEditing.value = false;
            fetchGateways();
        },
        onError: (err) => errors.value = err,
        onFinish: () => saving.value = false,
    });
};

const cancelEdit = () => {
    isEditing.value = false;
    form.reset();
};

// Add new parameter field dynamically
const addParameter = () => {
    const key = prompt('Enter parameter key (e.g. secret_key):');
    if (key) {
        form.gateway_parameters[key] = {
            title: key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' '),
            global: true,
            value: ''
        };
    }
};

const removeParameter = (key: string) => {
    delete form.gateway_parameters[key];
};

onMounted(fetchGateways);
</script>

<template>
    <AppLayout title="Gateway Manager">
        <div class="mx-auto max-w-7xl px-6 py-10">
            <div class="mb-8 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-white">Payment Gateway Configuration</h1>
                <button v-if="!isEditing" @click="isEditing = true"
                        class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-2xl text-white font-semibold flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add New Gateway
                </button>
            </div>

            <!-- FORM -->
            <div v-if="isEditing" class="bg-gray-800 rounded-3xl p-8">
                <h2 class="text-2xl font-semibold mb-6">{{ form.id ? 'Edit' : 'Create' }} Gateway</h2>

                <form @submit.prevent="saveGateway" class="space-y-8">
                    <!-- Basic Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-gray-400 block mb-2">Gateway Name *</label>
                            <input v-model="form.name" type="text" class="w-full bg-gray-700 border border-gray-600 rounded-2xl px-5 py-4 text-white" required />
                        </div>
                        <div>
                            <label class="text-gray-400 block mb-2">Alias *</label>
                            <input v-model="form.alias" type="text" class="w-full bg-gray-700 border border-gray-600 rounded-2xl px-5 py-4 text-white" required />
                        </div>
                    </div>

                    <!-- Gateway Parameters Section -->
                    <div class="bg-gray-900 rounded-2xl p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-white">Global Gateway Parameters</h3>
                            <button type="button" @click="addParameter"
                                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-xl text-sm">
                                + Add Parameter
                            </button>
                        </div>

                        <div v-for="(param, key) in form.gateway_parameters" :key="key"
                             class="grid grid-cols-12 gap-4 mb-4 bg-gray-800 p-4 rounded-2xl">
                            <div class="col-span-5">
                                <label class="text-gray-400 text-sm">Title</label>
                                <input v-model="param.title" type="text" class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-3 text-white" />
                            </div>
                            <div class="col-span-5">
                                <label class="text-gray-400 text-sm">Value</label>
                                <input v-model="param.value" type="text" class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-3 text-white" />
                            </div>
                            <div class="col-span-2 flex items-end">
                                <button type="button" @click="removeParameter(key)"
                                        class="text-red-500 hover:text-red-400">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-4">
                        <button type="submit" :disabled="saving"
                                class="flex-1 bg-green-600 hover:bg-green-700 py-4 rounded-2xl font-semibold text-white">
                            {{ saving ? 'Saving...' : 'Save Gateway' }}
                        </button>
                        <button type="button" @click="cancelEdit"
                                class="flex-1 bg-gray-600 hover:bg-gray-700 py-4 rounded-2xl font-semibold text-white">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
