<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { router } from '@inertiajs/vue3'

const props = defineProps<{
    payrolls: {
        data: any[];
    };
    start_date: string;
    end_date: string;
}>();

const startDate = ref(props.start_date);
const endDate = ref(props.end_date);

const search = () => {
    router.get(route('payrolls.index'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const selectedPayrolls = ref<number[]>([]);

const handleCheckboxChange = (payrollId: number, isChecked: boolean) => {
    if (isChecked) {
        if (!selectedPayrolls.value.includes(payrollId)) {
            selectedPayrolls.value.push(payrollId);
        }
    } else {
        const index = selectedPayrolls.value.indexOf(payrollId);
        if (index > -1) {
            selectedPayrolls.value.splice(index, 1);
        }
    }
};

const form = useForm({
    payroll_ids: [],
});

const closePayrolls = () => {
    form.transform(data => ({
        ...data,
        payroll_ids: selectedPayrolls.value,
    })).post(route('payrolls.close'), {
        onSuccess: () => {
            selectedPayrolls.value = [];
            search(); // Refresh the data
        },
    });
};

const isAllSelected = ref(false);

const handleSelectAll = (isChecked: boolean) => {
    isAllSelected.value = isChecked;
    if (isChecked) {
        selectedPayrolls.value = props.payrolls.data.filter(p => p.status !== 'Closed').map(p => p.id);
    } else {
        selectedPayrolls.value = [];
    }
}

</script>

<template>
    <Head title="Payroll" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payroll Transactions</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Payroll List</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-4 mb-4">
                            <Input type="date" v-model="startDate" class="w-auto" />
                            <span class="mx-2">to</span>
                            <Input type="date" v-model="endDate" class="w-auto" />
                            <Button @click="search">Filter</Button>
                            <Button @click="closePayrolls" :disabled="selectedPayrolls.length === 0" variant="destructive">
                                Close Selected ({{ selectedPayrolls.length }})
                            </Button>
                        </div>

                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[50px]">
                                            <Checkbox @update:checked="handleSelectAll" :checked="isAllSelected" />
                                        </TableHead>
                                        <TableHead>Employee</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead class="text-right">Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-if="payrolls.data.length > 0">
                                        <TableRow v-for="payroll in payrolls.data" :key="payroll.id">
                                            <TableCell>
                                                <Checkbox
                                                    v-if="payroll.status !== 'Closed'"
                                                    :checked="selectedPayrolls.includes(payroll.id)"
                                                    @update:checked="(isChecked) => handleCheckboxChange(payroll.id, Boolean(isChecked))"
                                                />
                                            </TableCell>
                                            <TableCell>{{ payroll.employee.name }}</TableCell>
                                            <TableCell>{{ payroll.start_date }} - {{ payroll.end_date }}</TableCell>
                                            <TableCell class="text-right">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(payroll.total_amount) }}</TableCell>
                                            <TableCell>{{ payroll.status }}</TableCell>
                                        </TableRow>
                                    </template>
                                    <template v-else>
                                        <TableRow>
                                            <TableCell colspan="5" class="text-center">
                                                No payroll data found for the selected date range.
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
