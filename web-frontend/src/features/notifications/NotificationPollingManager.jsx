import { useEffect, useRef, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchNotifications, fetchUnreadCount } from "./notificationsSlice";
import toast from "react-hot-toast";

const NotificationPollingManager = () => {
  const dispatch = useDispatch();
  const { unreadCount } = useSelector((state) => state.notifications);

 
  const [isPollingEnabled] = useState(false);

  const prevUnreadCountRef = useRef(unreadCount);

  useEffect(() => {
    
    dispatch(fetchUnreadCount());
    dispatch(fetchNotifications());

    let interval = null;

    
    if (isPollingEnabled) {
      interval = setInterval(() => {
        console.log("Polling for new notifications...");
        dispatch(fetchUnreadCount());
        dispatch(fetchNotifications());
      }, 15000); 
    }

   
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [dispatch, isPollingEnabled]);

 
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
