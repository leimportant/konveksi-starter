<template>
  <div class="push-notification-toggle">
    <div class="toggle-container">
      <label class="toggle-label" :class="{ 'disabled': !supported }">
        <span>{{ t('pushNotifications.title') }}</span>
        <div class="toggle-switch" @click="toggleSubscription" :class="{ 'disabled': !supported, 'loading': loading }">
          <div class="toggle-slider" :class="{ 'active': isSubscribed }">
            <div class="toggle-button"></div>
          </div>
        </div>
      </label>
    </div>
    <p v-if="!supported" class="unsupported-message">
      {{ t('pushNotifications.unsupported') }}
    </p>
    <p v-if="error" class="error-message">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import pushNotificationManager from '../push-notifications';
import { useToast } from '../composables/useToast';

const { t } = useI18n();
const toast = useToast();

// State
const supported = ref(false);
const isSubscribed = ref(false);
const loading = ref(false);
const error = ref('');

// Initialize on component mount
onMounted(async () => {
  try {
    loading.value = true;
    error.value = '';
    
    // Check if push notifications are supported
    supported.value = 'serviceWorker' in navigator && 'PushManager' in window;
    
    if (!supported.value) {
      console.log('Push notifications are not supported by this browser');
      return;
    }
    
    // Initialize push notification manager
    const initialized = await pushNotificationManager.initialize();
    
    if (initialized) {
      // Check if already subscribed
      isSubscribed.value = await pushNotificationManager.checkSubscription();
      console.log('Push notification status:', isSubscribed.value ? 'Subscribed' : 'Not subscribed');
    } else {
      error.value = t('pushNotifications.initFailed');
    }
  } catch (err) {
    console.error('Error initializing push notifications:', err);
    error.value = t('pushNotifications.errorOccurred');
  } finally {
    loading.value = false;
  }
});

// Toggle subscription status
async function toggleSubscription() {
  if (!supported.value || loading.value) {
    return;
  }
  
  try {
    loading.value = true;
    error.value = '';
    
    if (isSubscribed.value) {
      // Unsubscribe
      const success = await pushNotificationManager.unsubscribe();
      if (success) {
        isSubscribed.value = false;
        console.log('Successfully unsubscribed from push notifications');
        toast.success(t('pushNotifications.unsubscribeSuccess'));
      } else {
          error.value = t('pushNotifications.unsubscribeFailed');
          toast.error(t('pushNotifications.error'));
        }
    } else {
      // Subscribe
      const subscription = await pushNotificationManager.subscribe();
      if (subscription) {
        isSubscribed.value = true;
        console.log('Successfully subscribed to push notifications');
        toast.success(t('pushNotifications.subscribeSuccess'));
      } else {
          error.value = t('pushNotifications.subscribeFailed');
          toast.error(t('pushNotifications.error'));
        }
    }
  } catch (err) {
    console.error('Error toggling push notification subscription:', err);
    error.value = t('pushNotifications.errorOccurred');
    toast.error(t('pushNotifications.error'));
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.push-notification-toggle {
  margin: 1rem 0;
}

.toggle-container {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.toggle-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  cursor: pointer;
  font-weight: 500;
}

.toggle-label.disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.toggle-switch {
  position: relative;
  width: 50px;
  height: 24px;
  margin-left: 1rem;
  border-radius: 12px;
  background-color: #e0e0e0;
  transition: background-color 0.3s;
}

.toggle-switch.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toggle-switch.loading {
  opacity: 0.7;
}

.toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  transition: transform 0.3s, background-color 0.3s;
}

.toggle-slider.active {
  transform: translateX(26px);
  background-color: white;
}

.toggle-switch:not(.disabled):hover .toggle-slider {
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
}

.toggle-slider.active + .toggle-switch {
  background-color: #4CAF50;
}

.toggle-switch:not(.disabled):active .toggle-slider {
  width: 24px;
}

.unsupported-message {
  color: #666;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.error-message {
  color: #f44336;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

/* Active state styling */
.toggle-slider.active ~ .toggle-switch {
  background-color: #4CAF50;
}

.toggle-switch:has(.toggle-slider.active) {
  background-color: #4CAF50;
}
</style>