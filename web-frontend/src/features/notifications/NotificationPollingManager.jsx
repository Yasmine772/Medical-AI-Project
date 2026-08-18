import { useEffect, useRef } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchNotifications, fetchUnreadCount } from "./notificationsSlice";
import { getToken, onMessage } from "firebase/messaging";
import { messaging } from "../../firebase"; 
import api from "../../api/axios"; 
import toast from "react-hot-toast";

const NotificationPollingManager = () => {
  const dispatch = useDispatch();
  const { unreadCount } = useSelector((state) => state.notifications);
  const prevUnreadCountRef = useRef(unreadCount);

  useEffect(() => {
  
    dispatch(fetchUnreadCount());
    dispatch(fetchNotifications());

   
    const requestPermissionAndSendToken = async () => {
      try {
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
          
          const vapidKey =
            "BJOAbsWXIIJTNrMx9oEHLEgoV8jkb7K6izZd0lB9N88-w8Tla5DZmWFHrSoJk13wp4O29-zMy0L-BUpppi_0_Rg";

          const token = messaging ? await getToken(messaging, { vapidKey }) : null;

          if (token) {
            console.log("FCM Token Generated:", token);

            await api.post("admin/push-fcm-token", {
              fcm_token: token,
            });
            console.log("Token sent to backend successfully!");
          }
        }
      } catch (error) {
        console.error("Error handling FCM token:", error);
      }
    };

    requestPermissionAndSendToken();

    const unsubscribe = messaging
      ? onMessage(messaging, (payload) => {
      console.log("New foreground notification received: ", payload);
      toast.success(
        payload.notification?.title || "New notification received!",
      );

      dispatch(fetchUnreadCount());
      dispatch(fetchNotifications());
    })
      : null;

    return () => {
      if (unsubscribe) unsubscribe();
    };
  }, [dispatch]);

 
  useEffect(() => {
    if (
      prevUnreadCountRef.current !== null &&
      unreadCount > prevUnreadCountRef.current
    ) {
      toast.success("🔔 New join request");
    }
    prevUnreadCountRef.current = unreadCount;
  }, [unreadCount]);

  return null;
};

export default NotificationPollingManager;
