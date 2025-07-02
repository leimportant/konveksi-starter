<script setup lang="ts">
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import ScrollArea from '@/components/ui/ScrollArea.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { useChatStore } from '@/stores/chatStore';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

interface User {
  id: number;
  type: string | 'admin';
  name: string;
  avatar: string;
}

const page = usePage();
const user = page.props.auth.user as User;

console.log(user);

const breadcrumbs = [{ title: 'Pesan', href: '/messages' }];

const props = defineProps<{
  orderId?: string;
  currentUserId: number;
  currentUserType: 'admin' | 'customer';
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

const sendMessage = async () => {
  if (!newMessage.value.trim()) return;

  await chatStore.sendMessage({
    sender_type: props.currentUserType,
    receiver_id: props.receiverId,
    sender_name: props.currentUserName,
    message: newMessage.value,
    order_id: selectedOrderId.value!,
  });

  newMessage.value = '';
  await nextTick();
  scrollToBottom();
};

const selectOrder = async (orderId: string) => {
  selectedOrderId.value = orderId;
  await fetchMessages();
};

const goBack = () => {
  selectedOrderId.value = null;
};

onMounted(async () => {
  if (!props.orderId) {
    await chatStore.fetchConversations(); // Fetch list of chats
  } else {
    selectedOrderId.value = props.orderId;
    await fetchMessages();
  }
});

watch(() => props.orderId, fetchMessages, { immediate: true });
</script>

<template>
  <Head title="Pesan Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col h-full gap-4">
      <!-- List View -->
      <div v-if="!isViewingDetail" class="border rounded-lg p-4 bg-white">
        <h2 class="text-lg font-bold mb-4">Daftar Percakapan</h2>
        <ul class="space-y-2">
          <li
            v-for="conv in chatStore.conversations"
            :key="conv.order_id"
            class="cursor-pointer hover:bg-gray-100 p-3 rounded border"
            @click="selectOrder(conv.order_id)"
          >
            <div class="font-semibold">Order #{{ conv.order_id }}</div>
            <div class="text-sm text-gray-600 truncate">{{ conv.last_message }}</div>
          </li>
        </ul>
      </div>

      <!-- Detail View -->
      <div v-else class="flex flex-col border rounded-lg overflow-hidden bg-white h-[75vh]">
        <div class="flex items-center justify-between p-4 border-b">
          <h2 class="text-lg font-bold">Order #{{ selectedOrderId }}</h2>
          <Button variant="ghost" size="sm" @click="goBack">← Kembali</Button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-hidden p-4">
          <ScrollArea ref="chatContainer" class="h-full pr-4">
            <div class="space-y-4">
              <div
                v-for="msg in chatStore.messages"
                :key="msg.id"
                :class="['flex', msg.sender_id === props.currentUserId ? 'justify-end' : 'justify-start']"
              >
                <div
                  :class="[
                    'flex items-end gap-2',
                    msg.sender_id === props.currentUserId ? 'flex-row-reverse' : 'flex-row'
                  ]"
                >
                  <Avatar class="size-8">
                    <AvatarImage
                      v-if="msg.sender_id === props.currentUserId && props.currentUserAvatar"
                      :src="props.currentUserAvatar"
                      :alt="msg.sender_name"
                    />
                    <AvatarFallback>{{ getInitials(msg.sender_name ?? 'U') }}</AvatarFallback>
                  </Avatar>
                  <div
                    :class="[
                      'max-w-[70%] rounded-lg p-3 text-sm',
                      msg.sender_id === props.currentUserId
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 text-gray-800'
                    ]"
                  >
                    <p>{{ msg.message }}</p>
                    <span class="block text-xs mt-1 opacity-70">
                      {{
                        new Date(msg.created_at).toLocaleTimeString('id-ID', {
                          hour: '2-digit',
                          minute: '2-digit'
                        })
                      }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </ScrollArea>
        </div>

        <!-- Message Input -->
        <div class="border-t p-4">
          <div class="flex gap-2">
            <Input
              v-model="newMessage"
              placeholder="Ketik pesan..."
              @keyup.enter="sendMessage"
              class="flex-1"
            />
            <Button @click="sendMessage"  class="bg-indigo-600 p-2 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Kirim</Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
