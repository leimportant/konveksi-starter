<template>
  <div class="push-notification-demo">
    <h1>{{ t('pushNotifications.demoTitle') }}</h1>
    
    <div class="card">
      <div class="card-header">
        <h2>{{ t('pushNotifications.settings') }}</h2>
      </div>
      <div class="card-body">
        <p>{{ t('pushNotifications.description') }}</p>
        
        <!-- Toggle component using direct implementation -->
        <div class="toggle-section">
          <h3>{{ t('pushNotifications.directImplementation') }}</h3>
          <PushNotificationToggle />
        </div>
        
        <!-- Toggle component using store implementation -->
        <div class="toggle-section">
          <h3>{{ t('pushNotifications.storeImplementation') }}</h3>
          <PushNotificationToggleStore />
        </div>
      </div>
    </div>
    
    <!-- Test notification section -->
    <div class="card mt-4">
      <div class="card-header">
        <h2>{{ t('pushNotifications.testNotification') }}</h2>
      </div>
      <div class="card-body">
        <p>{{ t('pushNotifications.testDescription') }}</p>
        
        <div class="form-group">
          <label for="notificationTitle">{{ t('pushNotifications.title') }}</label>
          <input 
            type="text" 
            id="notificationTitle" 
            v-model="notificationTitle" 
            class="form-control" 
            :placeholder="t('pushNotifications.titlePlaceholder')"
          />
        </div>
        
        <div class="form-group">
          <label for="notificationBody">{{ t('pushNotifications.body') }}</label>
          <textarea 
            id="notificationBody" 
            v-model="notificationBody" 
            class="form-control" 
            :placeholder="t('pushNotifications.bodyPlaceholder')"
            rows="3"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label for="notificationUrl">{{ t('pushNotifications.url') }}</label>
          <input 
            type="text" 
            id="notificationUrl" 
            v-model="notificationUrl" 
            class="form-control" 
            :placeholder="t('pushNotifications.urlPlaceholder')"
          />
        </div>
        
        <button 
          @click="sendTestNotification" 
          class="btn btn-primary" 
          :disabled="isSending || !canSendNotification"
        >
          <span v-if="isSending">{{ t('pushNotifications.sending') }}</span>
          <span v-else>{{ t('pushNotifications.sendTest') }}</span>
        </button>
        
        <div v-if="sendResult" class="alert" :class="sendResult.success ? 'alert-success' : 'alert-danger'" role="alert">
          {{ sendResult.message }}
        </div>
      </div>
    </div>
    
    <!-- Documentation section -->
    <div class="card mt-4">
      <div class="card-header">
        <h2>{{ t('pushNotifications.documentation') }}</h2>
      </div>
      <div class="card-body">
        <p>{{ t('pushNotifications.documentationDescription') }}</p>
        <a href="/PUSH_NOTIFICATIONS.md" target="_blank" class="btn btn-outline-primary">
          {{ t('pushNotifications.viewDocumentation') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '../composables/useToast';
import axios from 'axios';
import PushNotificationToggle from '../components/PushNotificationToggle.vue';
import PushNotificationToggleStore from '../components/PushNotificationToggleStore.vue';
import { usePushNotificationStore } from '../stores/usePushNotificationStore';

const { t } = useI18n();
const toast = useToast();
const pushStore = usePushNotificationStore();

// Form state
const notificationTitle = ref('Test Notification');
const notificationBody = ref('This is a test push notification from the Vue Starter app.');
const notificationUrl = ref(window.location.origin);
const isSending = ref(false);
const sendResult = ref<{ success: boolean; message: string } | null>(null);

// Computed properties
const canSendNotification = computed(() => {
  return (
    notificationTitle.value.trim() !== '' && 
    notificationBody.value.trim() !== '' && 
    pushStore.isSubscribed
  );
});

// Send test notification
async function sendTestNotification() {
  if (!canSendNotification.value) {
    toast.error(t('pushNotifications.cannotSend'));
    return;
  }
  
  try {
    isSending.value = true;
    sendResult.value = null;
    
    const response = await axios.post('/api/push/send', {
      title: notificationTitle.value,
      body: notificationBody.value,
      url: notificationUrl.value || undefined
    });
    
    console.log('Test notification response:', response.data);
    sendResult.value = {
      success: true,
      message: t('pushNotifications.sendSuccess')
    };
    
    toast.success(t('pushNotifications.sendSuccess'));
  } catch (error) {
    console.error('Error sending test notification:', error);
    
    sendResult.value = {
      success: false,
      message: t('pushNotifications.sendError')
    };
    
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

.card {
  margin-bottom: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background-color: #f8f9fa;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.card-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.card-body {
  padding: 1.5rem;
}

.toggle-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.toggle-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.form-group {
  margin-bottom: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #495057;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  color: #495057;
  background-color: #fff;
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
  display: inline-block;
  font-weight: 400;
  text-align: center;
  white-space: nowrap;
  vertical-align: middle;
  user-select: none;
  border: 1px solid transparent;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  line-height: 1.5;
  border-radius: 0.25rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-primary {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  color: #fff;
  background-color: #0069d9;
  border-color: #0062cc;
}

.btn-primary:disabled {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
  opacity: 0.65;
}

.btn-outline-primary {
  color: #007bff;
  background-color: transparent;
  background-image: none;
  border-color: #007bff;
}

.btn-outline-primary:hover {
  color: #fff;
  background-color: #007bff;
  border-color: #007bff;
}

.alert {
  position: relative;
  padding: 0.75rem 1.25rem;
  margin-top: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
  border-color: #c3e6cb;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

.mt-4 {
  margin-top: 1.5rem;
}
</style>