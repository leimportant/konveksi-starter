// stores/chatStore.ts
import { defineStore } from 'pinia';
import axios from 'axios';

export interface ChatMessage {
  id: number;
  sender_id: number;
  sender: {
    id: number;
    name: string;
    email: string;
  };
  receiver_id: number;
  sender_type: string;
  sender_name: string;
  receiver_type: string;
  message: string;
  created_at: string;
  order_id: string;
  content_data?: string | string[] | any[];
}

export interface ChatConversation {
  order_id: string;
  last_message: string;
  unread_count: number;
}

export const useChatStore = defineStore('chat', {
  state: () => ({
    messages: [] as ChatMessage[],
    conversations: [] as ChatConversation[],
    unreadCount: 0,
    currentOrderId: null as string | null,
  }),

  actions: {
    async fetchMessages(orderId?: string) {
      if (!orderId) return;
      const { data } = await axios.get(`/api/chat/messages?order_id=${orderId}`);
      this.messages = data;
      this.currentOrderId = orderId;
      await this.markAsRead(orderId); // Mark as read when viewing
    },

    async sendMessage(payload: {
      sender_type: string;
      receiver_id: number;
      sender_name: string;
      content_data: string;
      message: string;
      order_id?: string;
    }) {
      const { data } = await axios.post('/api/chat/send', payload);
      this.messages.push(data.data);
    },

    async fetchConversations() {
      const { data } = await axios.get('/api/chat/conversations');
      this.conversations = data;
      this.updateUnreadCount();
    },

    updateUnreadCount() {
      this.unreadCount = this.conversations.reduce(
        (sum, conv) => sum + (conv.unread_count || 0),
        0
      );
    },

    async markAsRead(orderId: string) {
      const conv = this.conversations.find((c) => c.order_id === orderId);
      if (conv) {
        conv.unread_count = 0;
        this.updateUnreadCount();
      }
      try {
        await axios.post(`/api/chat/messages/${orderId}/read`);
      } catch (error) {
        console.error('Mark as read failed:', error);
      }
    },

    clearMessages() {
      this.messages = [];
      this.currentOrderId = null;
    },

    addMessage(message: ChatMessage) {
      this.messages.push(message);

      const conv = this.conversations.find((c) => c.order_id === message.order_id);

      // Jika pesan masuk bukan dari order yang sedang dibuka
      const isCurrentOpen = this.currentOrderId === message.order_id;

      if (conv) {
        conv.last_message = message.message;
        if (!isCurrentOpen) {
          conv.unread_count += 1;
        }
      } else {
        this.conversations.push({
          order_id: message.order_id,
          last_message: message.message,
          unread_count: isCurrentOpen ? 0 : 1,
        });
      }

      this.updateUnreadCount();
    },

    listOrderIds() {
      return this.conversations.map((conv) => conv.order_id);
    },
  },
});
