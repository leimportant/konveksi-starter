<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface Order {
    id: string;
    customer_id: number;
    total_amount: string;
    payment_method: string;
    payment_proof: string;
    is_paid: string;
    status: string;
    created_by: number;
    updated_by: number;
    created_at: string;
    updated_at: string;
    customer: {
        id: number;
        name: string;
        address: string;
        phone_number: string;
        saldo_kredit: string;
        is_active: string;
        created_by: number;
        updated_by: number;
        created_at: string;
        updated_at: string;
    };
}

const props = defineProps({
    message: {
        type: String,
        default: '',
    },
    order: {
        type: Object as () => Order | null,
        default: null,
    },
});

const message = ref<string>(props.message);
const order = ref<Order | null>(props.order);

const fetchOrderDetails = async () => {
    console.log('aaa');
    console.log(props.order);
    if (props.order) {
        try {
            const response = await axios.post(
                `/api/orders/${props.order.id}/status/approved`,
                {},
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            console.log('approve');
            console.log(response);
            order.value = response.data.order; // Assuming the API returns order data under 'order' key
            message.value = response.data.message || 'Order approved successfully!';
        } catch (error) {
            console.error('Error fetching order details:', error);
            message.value = 'Failed to fetch order details.';
            order.value = null;
        }
    } else {
        message.value = 'No order ID provided for approval.';
        order.value = null;
    }
};

onMounted(() => {
    fetchOrderDetails();
});
</script>

<template>
    <div class="container mx-auto p-4">
        <h1 class="mb-4 text-2xl font-bold">Order Approval Status</h1>

        <div v-if="message" class="relative mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">{{ message }}</span>
        </div>

        <div v-if="order && order.status === '1'" class="mb-4 rounded bg-white px-8 pb-8 pt-6 shadow-md">
            <h2 class="mb-4 text-xl font-semibold">Order Details</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <p><span class="font-semibold">Order ID:</span> {{ order.id }}</p>
                    <p><span class="font-semibold">Total Amount:</span> {{ order.total_amount }}</p>
                    <p><span class="font-semibold">Payment Method:</span> {{ order.payment_method }}</p>
                    <p><span class="font-semibold">Is Paid:</span> {{ order.is_paid === 'Y' ? 'Yes' : 'No' }}</p>
                    <p><span class="font-semibold">Status:</span> {{ order.status }}</p>
                </div>
                <div>
                    <p><span class="font-semibold">Customer Name:</span> {{ order.customer?.name || 'N/A' }}</p>
                    <p><span class="font-semibold">Customer Address:</span> {{ order.customer?.address || 'N/A' }}</p>
                    <p><span class="font-semibold">Customer Phone:</span> {{ order.customer?.phone_number || 'N/A' }}</p>
                </div>
            </div>
            <div v-if="order.payment_proof" class="mt-4">
                <p class="font-semibold">Payment Proof:</p>
                <img :src="`/storage/${order.payment_proof}`" alt="Payment Proof" class="mt-2 h-auto max-w-xs" />
            </div>
        </div>

        <div v-else class="relative rounded border border-yellow-400 bg-yellow-100 px-4 py-3 text-yellow-700" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">No order details available.</span>
        </div>
    </div>
</template>

<style scoped>
/* Add any component-specific styles here */
</style>
