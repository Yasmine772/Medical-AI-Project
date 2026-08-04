import { useEffect, useRef, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchNotifications, fetchUnreadCount } from "./notificationsSlice";
import toast from "react-hot-toast";

const NotificationPollingManager = () => {
  const dispatch = useDispatch();
  const { unreadCount } = useSelector((state) => state.notifications);

  // 1. مفتاح التشغيل والإيقاف (يمكنك جعله true للتشغيل أو false للإيقاف مؤقتاً)
  const [isPollingEnabled] = useState(false);

  const prevUnreadCountRef = useRef(unreadCount);

  useEffect(() => {
    // جلب البيانات أول ما تفتح الصفحة
    dispatch(fetchUnreadCount());
    dispatch(fetchNotifications());

    let interval = null;

    // 2. إذا كان مفتاح التشغيل true، نقوم بتفعيل الـ setInterval
    if (isPollingEnabled) {
      interval = setInterval(() => {
        console.log("Polling for new notifications...");
        dispatch(fetchUnreadCount());
        dispatch(fetchNotifications());
      }, 15000); // كل 15 ثانية
    }

    // التنظيف عند إغلاق المكون أو عند تغيير حالة الـ isPollingEnabled
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [dispatch, isPollingEnabled]); // <--- أضفنا isPollingEnabled هنا ليعيد بناء الـ Effect عند التغيير

  // مراقبة التغير في الـ unreadCount لإظهار التوست
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
