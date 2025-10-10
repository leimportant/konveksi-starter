/**
 * Push Notification Handler
 * 
 * This module handles push notification subscriptions and interactions.
 * It provides methods to subscribe, unsubscribe, and check subscription status.
 */

import axios from 'axios';

class PushNotificationManager {
    constructor() {
        this.swRegistration = null;
        this.isSubscribed = false;
        this.applicationServerPublicKey = window.vapidPublicKey || import.meta.env.VITE_VAPID_PUBLIC_KEY;
        
        // Bind methods to this instance
        this.initialize = this.initialize.bind(this);
        this.subscribe = this.subscribe.bind(this);
        this.unsubscribe = this.unsubscribe.bind(this);
        this.updateSubscriptionOnServer = this.updateSubscriptionOnServer.bind(this);
        this.removeSubscriptionFromServer = this.removeSubscriptionFromServer.bind(this);
        this.urlB64ToUint8Array = this.urlB64ToUint8Array.bind(this);
        this.checkSubscription = this.checkSubscription.bind(this);
    }

    /**
     * Initialize the push notification manager
     * @returns {Promise<boolean>} - Whether initialization was successful
     */
    async initialize() {
        try {
            // Check if service workers and push messaging are supported
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push notifications are not supported by this browser');
                return false;
            }

            // Check if we have a valid public key
            if (!this.applicationServerPublicKey) {
                console.error('VAPID public key is missing');
                return false;
            }

            // Get the service worker registration
            this.swRegistration = await navigator.serviceWorker.ready;
            console.log('Service Worker is ready', this.swRegistration);

            // Check if we're already subscribed
            await this.checkSubscription();
            
            return true;
        } catch (error) {
            console.error('Error initializing push notifications:', error);
            return false;
        }
    }

    /**
     * Check if the user is already subscribed to push notifications
     * @returns {Promise<boolean>} - Whether the user is subscribed
     */
    async checkSubscription() {
        try {
            if (!this.swRegistration) {
                return false;
            }

            const subscription = await this.swRegistration.pushManager.getSubscription();
            this.isSubscribed = subscription !== null;
            
            console.log('User is' + (this.isSubscribed ? '' : ' not') + ' subscribed to push notifications');
            
            if (this.isSubscribed) {
                console.log('Current subscription:', subscription);
            }
            
            return this.isSubscribed;
        } catch (error) {
            console.error('Error checking subscription:', error);
            return false;
        }
    }

    /**
     * Subscribe the user to push notifications
     * @returns {Promise<PushSubscription|null>} - The push subscription object or null if failed
     */
    async subscribe() {
        try {
            if (!this.swRegistration) {
                await this.initialize();
            }

            if (!this.swRegistration) {
                throw new Error('Service Worker registration not available');
            }

            // Convert the base64 public key to Uint8Array
            const applicationServerKey = this.urlB64ToUint8Array(this.applicationServerPublicKey);

            // Subscribe the user
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            console.log('User is now subscribed:', subscription);
            this.isSubscribed = true;

            // Send the subscription to the server
            await this.updateSubscriptionOnServer(subscription);

            return subscription;
        } catch (error) {
            console.error('Failed to subscribe user:', error);
            return null;
        }
    }

    /**
     * Unsubscribe the user from push notifications
     * @returns {Promise<boolean>} - Whether unsubscription was successful
     */
    async unsubscribe() {
        try {
            if (!this.swRegistration) {
                return false;
            }

            const subscription = await this.swRegistration.pushManager.getSubscription();
            
            if (!subscription) {
                console.log('No subscription to unsubscribe from');
                this.isSubscribed = false;
                return true;
            }

            // Store endpoint before unsubscribing for server removal
            const endpoint = subscription.endpoint;

            // Unsubscribe from push manager
            await subscription.unsubscribe();
            console.log('User is now unsubscribed');
            this.isSubscribed = false;

            // Remove the subscription from the server
            await this.removeSubscriptionFromServer(endpoint);

            return true;
        } catch (error) {
            console.error('Error unsubscribing:', error);
            return false;
        }
    }

    /**
     * Send the subscription to the server
     * @param {PushSubscription} subscription - The push subscription object
     * @returns {Promise<boolean>} - Whether the update was successful
     */
    async updateSubscriptionOnServer(subscription) {
        try {
            if (!subscription) {
                console.error('No subscription to send to server');
                return false;
            }

            // Convert the subscription to a format the server can understand
            const subscriptionJson = subscription.toJSON();

            // Send the subscription to the server
            const response = await axios.post('/api/push/subscribe', {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: subscriptionJson.keys.p256dh,
                    auth: subscriptionJson.keys.auth
                }
            });

            console.log('Subscription sent to server:', response.data);
            return response.data.success;
        } catch (error) {
            console.error('Error sending subscription to server:', error);
            return false;
        }
    }

    /**
     * Remove the subscription from the server
     * @param {string} endpoint - The subscription endpoint
     * @returns {Promise<boolean>} - Whether the removal was successful
     */
    async removeSubscriptionFromServer(endpoint) {
        try {
            if (!endpoint) {
                console.error('No endpoint to remove from server');
                return false;
            }

            // Send the unsubscribe request to the server
            const response = await axios.post('/api/push/unsubscribe', {
                endpoint: endpoint
            });

            console.log('Subscription removed from server:', response.data);
            return response.data.success;
        } catch (error) {
            console.error('Error removing subscription from server:', error);
            return false;
        }
    }

    /**
     * Convert a base64 string to a Uint8Array for the applicationServerKey
     * @param {string} base64String - The base64 encoded string
     * @returns {Uint8Array} - The converted Uint8Array
     */
    urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
}

// Create and export a singleton instance
const pushNotificationManager = new PushNotificationManager();
export default pushNotificationManager;