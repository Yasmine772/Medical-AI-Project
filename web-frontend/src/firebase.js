import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging"; 

const firebaseConfig = {
  apiKey: "AIzaSyB5ZDYLI4RINvIbnqE5HAiPAh6lMRlS7Go",
  authDomain: "medical-ai-project-35a8d.firebaseapp.com",
  projectId: "medical-ai-project-35a8d",
  storageBucket: "medical-ai-project-35a8d.firebasestorage.app",
  messagingSenderId: "535737760085",
  appId: "1:535737760085:web:78c4dcb6e30284e0b997dc",
  measurementId: "G-Z775NWC029"
};

const app = initializeApp(firebaseConfig);

// Firebase Messaging requires a secure context (HTTPS or localhost) and a service worker.
// Initializing it in an insecure context (e.g. http://192.168.x.x) throws and would crash
// the whole app, so only set it up when both are available.
export let messaging = null;
if (typeof window !== "undefined" && "serviceWorker" in navigator && window.isSecureContext) {
  try {
    messaging = getMessaging(app);
  } catch (e) {
    console.warn("Firebase messaging unavailable:", e?.message || e);
    messaging = null;
  }
}

export const listenForMessages = () => {
  if (!messaging) return;
  console.log('Setting up notification listener (Foreground)...');
  
  onMessage(messaging, (payload) => {
    console.log('FOREGROUND NOTIFICATION RECEIVED!');
    console.log('Payload:', payload);
    
    const { title, body } = payload.notification || {};
    const data = payload.data || {};
    
    console.log(` Title: ${title}`);
    console.log(` Body: ${body}`);
    
    if (Notification.permission === 'granted') {
      const notification = new Notification(title || 'New Notification', {
        body: body || 'You have a new notification',
        icon: '/favicon.ico',
        requireInteraction: true,
        data: data
      });
      
      notification.onclick = function() {
        window.focus();
        if (data.click_action) {
          window.location.href = data.click_action;
        }
        this.close();
      };
    }
  });
};

export const getFCMToken = async () => {
  if (!messaging) return null;
  try {
    const permission = await Notification.requestPermission();
    
    if (permission === 'granted') {
      console.log('Permission granted');
      
      const token = await getToken(messaging, {
        vapidKey: 'BJOAbSWXIIJTNrMx9oEHLEgOV8jkb7K6izZd0LB9N88-w8TLa5DZmWFHrSoJk13wp4029-zMy0L-BUpppi_θ_Rg'
      });
      
      console.log('FCM Token:', token);
      localStorage.setItem('fcm_token', token);
      return token;
    } else {
      console.log('Permission denied');
      return null;
    }
  } catch (error) {
    console.error('Error getting token:', error);
    return null;
  }
};

export { firebaseConfig };