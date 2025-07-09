<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ScrollArea from '@/components/ui/ScrollArea.vue';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ChatMessage } from '@/stores/chatStore'; // ✅ konsisten
import { useChatStore } from '@/stores/chatStore';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface User {
    id: number;
    type: string | 'admin';
    name: string;
    avatar: string;
    employee_status: string | 'admin';
}

const page = usePage();
const user = page.props.auth.user as User;
const userType = user.employee_status == 'customer' ? 'customer' : 'admin';
console.log('user chat');
console.log(user.employee_status);

const breadcrumbs = [{ title: 'Pesan', href: '/messages' }];

const props = defineProps<{
    orderId?: string;
    currentUserId: number;
    currentUserName: string;
    currentUserAvatar?: string;
    receiverId: number;
}>();

const chatStore = useChatStore();
const newMessage = ref('');
const chatContainer = ref<HTMLElement | null>(null);

const selectedOrderId = ref<string | null>(props.orderId ?? null);
const isViewingDetail = computed(() => !!selectedOrderId.value);

const scrollToBottom = () => {
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const fetchMessages = async () => {
    if (!selectedOrderId.value) return;
    await chatStore.fetchMessages(selectedOrderId.value);
    await nextTick();
    scrollToBottom();
};
const getMessageClasses = (msg: ChatMessage) => {
    const isSender = msg.sender_id === user.id;
    return {
        container: isSender ? 'justify-end' : 'justify-start',
        row: isSender ? 'flex-row-reverse' : 'flex-row',
        bubble: isSender ? 'bg-indigo-600 text-white' : 'border bg-white text-gray-800',
    };
};

const getSenderName = (msg: ChatMessage): string => {
    return msg.sender_type === 'admin' ? 'AD' : getInitials(msg.sender?.name ?? 'User');
};

const formatTimestamp = (dateStr: string): string => {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) {
        date.setTime(Date.now());
    }
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const parseContentData = (data: string | any[]): any[] => {
    try {
        return Array.isArray(data) ? data : JSON.parse(data || '[]');
    } catch {
        return [];
    }
};

const markAsRead = async (orderId: string) => {
    await chatStore.markAsRead(orderId);
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    await chatStore.sendMessage({
        sender_type: userType,
        receiver_id: props.receiverId,
        sender_name: props.currentUserName,
        message: newMessage.value,
        order_id: selectedOrderId.value!,
        content_data: '', // Add content_data property as empty string or appropriate value
    });

    newMessage.value = '';
    await nextTick();
    scrollToBottom();
};

const selectOrder = async (orderId: string) => {
    selectedOrderId.value = orderId;
    await fetchMessages();
    await markAsRead(orderId);
};

const goBack = () => {
    selectedOrderId.value = null;
};

onMounted(async () => {
    if (!props.orderId) {
        await chatStore.fetchConversations();
    } else {
        selectedOrderId.value = props.orderId;
        await fetchMessages();
    }
});

const intervalId = null;

watch(selectedOrderId, (newOrderId, oldOrderId) => {
    if (intervalId) {
        clearInterval(intervalId);
    }

    if (newOrderId) {
        chatStore.fetchMessages(newOrderId);
        // Subscribe to Pusher channel
        window.Echo.private(`chat.${newOrderId}`).listen('ChatMessageSent', (e: any) => {
            chatStore.addMessage(e.chatMessage as ChatMessage);
        });

        // Optional: Set up interval for periodic historical data retrieval
        // intervalId = setInterval(() => {
        //   chatStore.fetchMessages(newOrderId);
        // }, 30000); // Fetch every 30 seconds
    } else if (oldOrderId) {
        // Unsubscribe from old channel when orderId changes to null or newOrderId
        window.Echo.leave(`chat.${oldOrderId}`);
    }
});

onUnmounted(() => {
    if (selectedOrderId.value) {
        window.Echo.leave(`chat.${selectedOrderId.value}`);
    }
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <Head title="Pesan Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-4 p-2">
            <!-- List View -->
            <div v-if="!isViewingDetail" class="bg-gray-40 gap-2 rounded-lg p-4 py-2">
                <ul class="space-y-2">
                    <li
                        v-for="conv in chatStore.conversations"
                        :key="conv.order_id"
                        class="cursor-pointer rounded border p-3 hover:bg-gray-100"
                        @click="selectOrder(conv.order_id)"
                    >
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">Order #{{ conv.order_id }}</div>
                            <span
                                v-if="conv.unread_count > 0"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white"
                            >
                                {{ conv.unread_count }}
                            </span>
                        </div>
                        <div class="truncate text-sm text-gray-600">{{ conv.last_message }}</div>
                    </li>
                </ul>
            </div>

            <!-- Detail View -->
            <div v-else class="flex h-[75vh] flex-col overflow-hidden rounded-lg border bg-white">
                <!-- Header -->
                <div class="flex items-center justify-between border-b bg-gray-50 p-4">
                    <h2 class="text-lg font-semibold">Order #{{ selectedOrderId }}</h2>
                    <Button variant="ghost" size="sm" class="text-indigo-600 hover:bg-indigo-100" @click="goBack">← Kembali</Button>
                </div>

                <!-- Chat Messages -->
                <div class="flex-1 overflow-hidden bg-gray-50 p-4">
                    <ScrollArea ref="chatContainer" class="h-full pr-4">
                        <div class="space-y-4">
                            <div v-for="msg in chatStore.messages" :key="msg.id" :class="['flex', getMessageClasses(msg).container]">
                                <div :class="['flex max-w-full items-start gap-3', getMessageClasses(msg).row]">
                                    <!-- Avatar -->
                                    <Avatar class="size-10 shrink-0">
                                        <AvatarImage
                                            v-if="msg.sender_id === props.currentUserId && props.currentUserAvatar"
                                            :src="props.currentUserAvatar"
                                            :alt="msg.sender_name"
                                        />
                                        <AvatarFallback>
                                            {{ getSenderName(msg) }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <!-- Bubble -->
                                    <div class="max-w-[75%] space-y-1">
                                        <div :class="['rounded-lg p-3 text-sm shadow', getMessageClasses(msg).bubble]">
                                            <p class="whitespace-pre-wrap break-words">{{ msg.message }}</p>
                                        </div>

                                        <!-- Content Data -->
                                        <div v-if="msg.content_data?.length" class="mt-1 flex flex-wrap gap-2">
                                            <span v-for="item in parseContentData(msg.content_data)" :key="item" class="...">
                                                {{ item }}
                                            </span>
                                        </div>

                                        <!-- Timestamp -->
                                        <div class="text-xs text-gray-500">
                                            {{ formatTimestamp(msg.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ScrollArea>
                </div>

                <!-- Input Area -->
                <div class="border-t bg-white p-4">
                    <div class="flex items-center gap-2">
                        <Input
                            v-model="newMessage"
                            placeholder="Ketik pesan..."
                            @keyup.enter="sendMessage"
                            class="flex-1 rounded-md border px-4 py-2 text-sm"
                        />
                        <Button
                            @click="sendMessage"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-400"
                        >
                            Kirim
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
