// stores/chatStore.ts
import { defineStore } from 'pinia';
import axios from 'axios';

export interface ChatMessage {
  id: number;
  sender_id: number;
  sender_name: string;
  message: string;
  created_at: string;
  order_id: string;
}

export interface ChatConversation {
  order_id: string;
  last_message: string;
}

export const useChatStore = defineStore('chat', {
  state: () => ({
    messages: [] as ChatMessage[],
    conversations: [] as ChatConversation[],
    unreadCount: 0,
  }),

  actions: {
    async fetchMessages(orderId?: string) {
      if (!orderId) return;
      const { data } = await axios.get(`/api/chat/messages?order_id=${orderId}`);
      this.messages = data;
    },

    async sendMessage(payload: {
      sender_type: 'admin' | 'customer';
      receiver_id: number;
      sender_name: string;
      message: string;
      order_id?: string;
    }) {
      const { data } = await axios.post('/api/chat/send', payload);
      this.messages.push(data);
    },

    async fetchConversations() {
      const { data } = await axios.get('/api/chat/conversations');
      this.conversations = data;
    },

    clearMessages() {
      this.messages = [];
    },

    listOrderIds() {
      return this.conversations.map(conv => conv.order_id);
    },
  },
});
