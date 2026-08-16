import { initializeApp } from "firebase/app";
import { getMessaging } from "firebase/messaging";

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
export const messaging = getMessaging(app);
export { firebaseConfig };