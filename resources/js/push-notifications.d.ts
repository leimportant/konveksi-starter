/**
 * Type declarations for push-notifications.js
 */

declare class PushNotificationManager {
  swRegistration: ServiceWorkerRegistration | null;
  isSubscribed: boolean;
  applicationServerPublicKey: string;

  constructor();
  
  /**
   * Initialize the push notification manager
   * @returns Whether initialization was successful
   */
  initialize(): Promise<boolean>;
  
  /**
   * Check if the user is already subscribed to push notifications
   * @returns Whether the user is subscribed
   */
  checkSubscription(): Promise<boolean>;
  
  /**
   * Subscribe the user to push notifications
   * @returns The push subscription object or null if failed
   */
  subscribe(): Promise<PushSubscription | null>;
  
  /**
   * Unsubscribe the user from push notifications
   * @returns Whether unsubscription was successful
   */
  unsubscribe(): Promise<boolean>;
  
  /**
   * Send the subscription to the server
   * @param subscription The push subscription object
   * @returns Whether the update was successful
   */
  updateSubscriptionOnServer(subscription: PushSubscription): Promise<boolean>;
  
  /**
   * Remove the subscription from the server
   * @param endpoint The subscription endpoint
   * @returns Whether the removal was successful
   */
  removeSubscriptionFromServer(endpoint: string): Promise<boolean>;
  
  /**
   * Convert a base64 string to a Uint8Array for the applicationServerKey
   * @param base64String The base64 encoded string
   * @returns The converted Uint8Array
   */
  urlB64ToUint8Array(base64String: string): Uint8Array;
}

declare const pushNotificationManager: PushNotificationManager;
export default pushNotificationManager;