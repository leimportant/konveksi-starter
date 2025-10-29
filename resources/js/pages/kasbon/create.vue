<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import { ref, Ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus } from 'lucide-vue-next';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';
import { useToast } from '@/composables/useToast';
import { useUserStore } from '@/stores/useUserStore';
import { storeToRefs } from 'pinia';
import { useKasbonStore } from '@/stores/useKasbonStore';
import { debounce } from 'lodash-es';

const kasbonStore = useKasbonStore();
const toast = useToast();
const breadcrumbs = [{ title: 'Kasbon', href: `/kasbon/create` }];

// 📦 Ambil data karyawan dari store
const userStore = useUserStore();
const { users: employees } = storeToRefs(userStore);

const page = usePage();
const user = page.props.auth.user;

// Form state pakai Inertia useForm
const form = useForm({
    employee_id: null as number | null,
    amount: '' as string,
    description: '' as string,
});

// Selected employee (v-model)
const selectedEmployeeId: Ref<number | { id: number; name: string }> = ref(0);

// Submit handler
const submit = async () => {
    try {

        const employeeId =
            typeof selectedEmployeeId.value === 'number'
                ? selectedEmployeeId.value
                : selectedEmployeeId.value?.id ?? 0;

        await kasbonStore.storeKasbon({
            amount: Number(form.amount),
            employee_id: employeeId, // send just the ID
            description: form.description || '',
            status: 'Pending',
        });


        toast.success('Kasbon berhasil dibuat');
        router.visit(`/home`);
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Gagal membuat kasbon');
    }
};

const onSearch = debounce((search: string, loading: (val: boolean) => void) => {
    if(search.length) {
        loading(true);
        userStore.fetchUsers(1, 10, user.employee_status.toUpperCase(), search.toUpperCase()).finally(() => loading(false));
    }
}, 500);
</script>

<template>

    <Head title="Buat Kasbon" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="space-y-6 p-6">
            <h1 class="text-xl font-semibold mb-6">Buat Kasbon</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Employee -->
                <div>
                    <label for="employee_id" class="block text-sm font-medium mb-1">Employee</label>
                    <Vue3Select id="model.id" v-model="selectedEmployeeId" :options="employees" label="name"
                        placeholder="Select Employee" class="w-full" :filterable="false" @search="onSearch" />
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium mb-1">Jumlah Kasbon</label>
                    <Input id="amount" type="number" v-model="form.amount" placeholder="1000" class="w-full" />
                    <div v-if="form.errors.amount" class="text-red-500 text-sm mt-1">
                        {{ form.errors.amount }}
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium mb-1">Alasan</label>
                    <Input id="description" type="text" v-model="form.description" placeholder="Isi alasan kasbon"
                        class="w-full" />
                    <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                        {{ form.errors.description }}
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex space-x-2 justify-end">
                    <Button type="submit" :disabled="form.processing"
                        class="rounded text-sm bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                        <Plus class="w-4 h-4 mr-2" />
                        Create
                    </Button>
                    <Button @click="router.visit(`/home`)" type="button"
                        class="rounded text-sm bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
