import { useState, useRef, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchNotifications } from "./notificationsSlice";
import { Bell, Loader2, CheckCircle2 } from "lucide-react";

const NotificationDropdown = () => {
  const [isOpen, setIsOpen] = useState(false);
  const dispatch = useDispatch();
  const dropdownRef = useRef(null);

  const { list: notifications, loading } = useSelector(
    (state) => state.notifications,
  );

  // إحضار الإشعارات فقط عند الضغط لفتح القائمة
  const handleToggleDropdown = () => {
    if (!isOpen) {
      dispatch(fetchNotifications());
    }
    setIsOpen(!isOpen);
  };

  // إغلاق القائمة عند النقر خارجها
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={dropdownRef}>
      {/* زر الجرس */}
      <button
        onClick={handleToggleDropdown}
        className="relative p-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors focus:outline-none"
        aria-label="Notifications"
      >
        <Bell className="w-5 h-5" />
        {notifications.some((n) => !n.read_at) && (
          <span className="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
        )}
      </button>

      {/* قائمة الإشعارات المنبثقة */}
      {isOpen && (
        <div className="absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
          {/* رأس القائمة */}
          <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <h3 className="font-bold text-gray-800 text-sm">Notifications</h3>
            <span className="text-xs bg-[#58889B]/10 text-[#58889B] font-semibold px-2 py-0.5 rounded-full">
              {notifications.length} New
            </span>
          </div>

          {/* محتوى القائمة */}
          <div className="max-h-80 overflow-y-auto divide-y divide-gray-50">
            {loading ? (
              <div className="flex items-center justify-center py-8 text-gray-400">
                <Loader2 className="w-6 h-6 animate-spin text-[#58889B]" />
              </div>
            ) : notifications.length === 0 ? (
              <div className="text-center py-8 text-gray-400 text-sm">
                No notifications found
              </div>
            ) : (
              notifications.map((notif) => (
                <div
                  key={notif.id}
                  className={`p-4 transition-colors hover:bg-gray-50 flex gap-3 items-start ${
                    !notif.read_at ? "bg-blue-50/30" : ""
                  }`}
                >
                  <div className="p-2 rounded-xl bg-[#58889B]/10 text-[#58889B] shrink-0 mt-0.5">
                    <CheckCircle2 className="w-4 h-4" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-bold text-gray-900 truncate">
                      {notif.data?.title || "Notification"}
                    </p>
                    <p className="text-xs text-gray-600 mt-1 leading-relaxed">
                      {notif.data?.message || "You have a new update."}
                    </p>
                    <span className="text-[10px] text-gray-400 mt-2 block">
                      {new Date(notif.created_at).toLocaleDateString()} -{" "}
                      {new Date(notif.created_at).toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationDropdown;
