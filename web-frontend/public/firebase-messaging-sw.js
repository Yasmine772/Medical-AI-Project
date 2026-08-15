/* eslint-disable no-undef */
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyB5ZDYLI4RINvIbnqE5HAiPAh6lMRlS7Go",
  authDomain: "medical-ai-project-35a8d.firebaseapp.com",
  projectId: "medical-ai-project-35a8d",
  storageBucket: "medical-ai-project-35a8d.firebasestorage.app",
  messagingSenderId: "535737760085",
  appId: "1:535737760085:web:78c4dcb6e30284e0b997dc"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  
  const notificationTitle = payload.notification?.title || "New Notification";
  const notificationOptions = {
    body: payload.notification?.body || "You have a new update.",
    icon: "/favicon.ico" 
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});