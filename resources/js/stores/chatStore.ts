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
  content_data?: string | string[] | any[]; // tambahkan ini
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
  }),

  actions: {
    async fetchMessages(orderId?: string) {
      if (!orderId) return;
      const { data } = await axios.get(`/api/chat/messages?order_id=${orderId}`);
      this.messages = data;
    },

    async sendMessage(payload: {
      sender_type: string;
      receiver_id: number;
      sender_name: string;
      content_data : string;
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

   // Di dalam chatStore
   async markAsRead(orderId: string) {
      const conv = this.conversations.find(c => c.order_id === orderId);
      if (conv) {
        conv.unread_count = 0;
      }

      // Optional: kasih tahu server
      await axios.post(`/api/chat/messages/${orderId}/read`);
    },


    clearMessages() {
      this.messages = [];
    },

    listOrderIds() {
      return this.conversations.map(conv => conv.order_id);
    },
  },
});
