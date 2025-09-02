import { ChatbotApi } from '@/types/global';

declare module 'vue' {
  interface ComponentCustomProperties {
    $chatbotUrl: string;
    $chatbot: ChatbotApi;
  }
}