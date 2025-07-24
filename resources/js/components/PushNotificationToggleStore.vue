<template>
  <div class="push-notification-toggle">
    <div class="toggle-container">
      <label class="toggle-label" :class="{ 'disabled': !isSupported }">
        <span>{{ t('pushNotifications.title') }}</span>
        <div class="toggle-switch" @click="toggleSubscription" :class="{ 'disabled': !isSupported, 'loading': isLoading }">
          <div class="toggle-slider" :class="{ 'active': isSubscribed }">
            <div class="toggle-button"></div>
          </div>
        </div>
      </label>
    </div>
    <p v-if="!isSupported" class="unsupported-message">
      {{ t('pushNotifications.unsupported') }}
    </p>
    <p v-if="error" class="error-message">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePushNotificationStore } from '../stores/usePushNotificationStore';
import { useToast } from '../composables/useToast';

const { t } = useI18n();
const toast = useToast();
const pushStore = usePushNotificationStore();

// State
const isLoading = ref(false);

// Computed properties
const isSupported = computed(() => 'serviceWorker' in navigator && 'PushManager' in window);
const isSubscribed = computed(() => pushStore.isSubscribed);
const error = computed(() => pushStore.error);

// Initialize on component mount
onMounted(async () => {
  try {
    isLoading.value = true;
    
    if (!isSupported.value) {
      console.log('Push notifications are not supported by this browser');
      return;
    }
    
    // Check if already subscribed
    await pushStore.checkSubscription();
    console.log('Push notification status:', isSubscribed.value ? 'Subscribed' : 'Not subscribed');
  } catch (err) {
    console.error('Error initializing push notifications:', err);
  } finally {
    isLoading.value = false;
  }
});

// Toggle subscription status
async function toggleSubscription() {
  if (!isSupported.value || isLoading.value) {
    return;
  }
  
  try {
    isLoading.value = true;
    
    if (isSubscribed.value) {
      // Unsubscribe
      await pushStore.unsubscribe();
      toast.success(t('pushNotifications.unsubscribeSuccess'));
    } else {
      // Subscribe
      await pushStore.subscribe();
      toast.success(t('pushNotifications.subscribeSuccess'));
    }
  } catch (err) {
    console.error('Error toggling push notification subscription:', err);
    toast.error(t('pushNotifications.error'));
  } finally {
    isLoading.value = false;
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
</style>