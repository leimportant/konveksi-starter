<template>
  <div class="push-notification-demo">
    <h1 class="text-2xl font-bold mb-6">{{ t('pushNotifications.demoTitle') }}</h1>

    <!-- Notification Settings Section -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-2">{{ t('pushNotifications.settings') }}</h2>
      <p class="text-gray-600 mb-4">{{ t('pushNotifications.description') }}</p>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Direct Implementation -->
        <div class="p-4 border rounded-lg">
          <h3 class="font-medium mb-2">{{ t('pushNotifications.directImplementation') }}</h3>
          <PushNotificationToggle />
        </div>

        <!-- Store Implementation -->
        <div class="p-4 border rounded-lg">
          <h3 class="font-medium mb-2">{{ t('pushNotifications.storeImplementation') }}</h3>
          <PushNotificationToggleStore />
        </div>
      </div>
    </div>

    <!-- Test Notification Section -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-2">{{ t('pushNotifications.testNotification') }}</h2>
      <p class="text-gray-600 mb-4">{{ t('pushNotifications.testDescription') }}</p>

      <div class="space-y-4">
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ t('pushNotifications.title') }}</label>
          <input
            id="title"
            v-model="notificationTitle"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            :placeholder="t('pushNotifications.titlePlaceholder')"
          />
        </div>

        <div>
          <label for="body" class="block text-sm font-medium text-gray-700 mb-1">{{ t('pushNotifications.body') }}</label>
          <textarea
            id="body"
            v-model="notificationBody"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            :placeholder="t('pushNotifications.bodyPlaceholder')"
            rows="3"
          ></textarea>
        </div>

        <div>
          <label for="url" class="block text-sm font-medium text-gray-700 mb-1">{{ t('pushNotifications.url') }}</label>
          <input
            id="url"
            v-model="notificationUrl"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            :placeholder="t('pushNotifications.urlPlaceholder')"
          />
        </div>

        <div class="pt-2">
          <button
            @click="sendTestNotification"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            :disabled="isSending || !isSubscribed"
          >
            <span v-if="isSending">{{ t('pushNotifications.sending') }}</span>
            <span v-else>{{ t('pushNotifications.sendTest') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Documentation Section -->
    <div class="p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-2">{{ t('pushNotifications.documentation') }}</h2>
      <p class="text-gray-600 mb-4">{{ t('pushNotifications.documentationDescription') }}</p>
      <a
        href="/PUSH_NOTIFICATIONS.md"
        target="_blank"
        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        {{ t('pushNotifications.viewDocumentation') }}
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';
import PushNotificationToggle from '@/components/PushNotificationToggle.vue';
import PushNotificationToggleStore from '@/components/PushNotificationToggleStore.vue';
import { usePushNotificationStore } from '@/stores/usePushNotificationStore';
import axios from 'axios';

const { t } = useI18n();
const toast = useToast();
const pushStore = usePushNotificationStore();

// Form data
const notificationTitle = ref('Test Notification');
const notificationBody = ref('This is a test notification from the demo page.');
const notificationUrl = ref('');
const isSending = ref(false);

// Computed property to check if user is subscribed
const isSubscribed = computed(() => {
  return pushStore.isSubscribed;
});

// Send test notification
async function sendTestNotification() {
  if (!isSubscribed.value) {
    toast.error(t('pushNotifications.cannotSend'));
    return;
  }

  isSending.value = true;

  try {
    await axios.post('/api/push/send', {
      title: notificationTitle.value,
      body: notificationBody.value,
      url: notificationUrl.value || null
    });

    toast.success(t('pushNotifications.sendSuccess'));
  } catch (error) {
    console.error('Failed to send notification:', error);
    toast.error(t('pushNotifications.sendError'));
  } finally {
    isSending.value = false;
  }
}
</script>

<style scoped>
.push-notification-demo {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}
</style>