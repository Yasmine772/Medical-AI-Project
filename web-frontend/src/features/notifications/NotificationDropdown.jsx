import { useState, useRef, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import {
  fetchNotifications,
  markNotificationAsRead,
  markAllNotificationsAsRead,
  fetchUnreadCount,
} from "./notificationsSlice";
import { Bell, Loader2, CheckCircle2, Check, CheckCheck } from "lucide-react";
import toast from "react-hot-toast";

const NotificationDropdown = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState("all");
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const dropdownRef = useRef(null);

  const {
    list: notifications,
    unreadCount,
    loading,
  } = useSelector((state) => state.notifications);

  useEffect(() => {
    dispatch(fetchUnreadCount());
    dispatch(fetchNotifications());
  }, [dispatch]);

  const handleToggleDropdown = () => {
    setIsOpen(!isOpen);
  };

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleMarkAsRead = (id) => {
    dispatch(markNotificationAsRead(id))
      .unwrap()
      .then(() => {
        toast.success("Notification read successfully");
        dispatch(fetchUnreadCount());
      })
      .catch((err) => {
        toast.error(err || "Failed to mark as read");
      });
  };

  const handleMarkAllAsRead = () => {
    dispatch(markAllNotificationsAsRead())
      .unwrap()
      .then(() => {
        toast.success("All notifications marked as read");
        dispatch(fetchUnreadCount());
      })
      .catch((err) => {
        toast.error(err || "Failed to mark all as read");
      });
  };

  const handleNotificationClick = (notif) => {
    if (!notif.read_at) {
      handleMarkAsRead(notif.id);
    }
    setIsOpen(false);

    navigate("/app/doctors", { state: { activeTab: "Join Requests" } });
  };

  const filteredNotifications = notifications.filter((notif) => {
    if (activeTab === "unread") return !notif.read_at;
    return true;
  });

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={handleToggleDropdown}
        className="p-2 bg-white rounded-full shadow-md hover:bg-gray-50 transition flex items-center justify-center relative focus:outline-none"
        aria-label="Notifications"
      >
        <Bell className="w-6 h-6 text-amber-500 fill-amber-100" />
        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
          <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
            <h3 className="font-bold text-gray-800 text-sm">Notifications</h3>
            {unreadCount > 0 && (
              <button
                onClick={handleMarkAllAsRead}
                className="flex items-center gap-1 text-[11px] font-semibold text-[#58889B] hover:text-[#456c7b] transition-colors bg-white px-2 py-1 rounded-md border border-gray-200 shadow-sm"
                title="Mark all as read"
              >
                <CheckCheck className="w-3.5 h-3.5" />
                Mark all read
              </button>
            )}
          </div>

          <div className="flex border-b border-gray-100 bg-gray-50/50 px-3 pt-2 gap-2">
            <button
              onClick={() => setActiveTab("all")}
              className={`pb-2 px-3 text-xs font-semibold transition-all border-b-2 ${
                activeTab === "all"
                  ? "border-[#58889B] text-[#58889B]"
                  : "border-transparent text-gray-500 hover:text-gray-700"
              }`}
            >
              All ({notifications.length})
            </button>
            <button
              onClick={() => setActiveTab("unread")}
              className={`pb-2 px-3 text-xs font-semibold transition-all border-b-2 flex items-center gap-1.5 ${
                activeTab === "unread"
                  ? "border-[#58889B] text-[#58889B]"
                  : "border-transparent text-gray-500 hover:text-gray-700"
              }`}
            >
              Unread
              {unreadCount > 0 && (
                <span className="bg-[#58889B]/10 text-[#58889B] px-1.5 py-0.2 rounded-full text-[10px]">
                  {unreadCount}
                </span>
              )}
            </button>
          </div>

          <div className="max-h-80 overflow-y-auto divide-y divide-gray-50">
            {loading && notifications.length === 0 ? (
              <div className="flex items-center justify-center py-8 text-gray-400">
                <Loader2 className="w-6 h-6 animate-spin text-[#58889B]" />
              </div>
            ) : filteredNotifications.length === 0 ? (
              <div className="text-center py-8 text-gray-400 text-sm">
                {activeTab === "unread"
                  ? "No unread notifications"
                  : "No notifications found"}
              </div>
            ) : (
              filteredNotifications.map((notif) => (
                <div
                  key={notif.id}
                  onClick={() => handleNotificationClick(notif)}
                  className={`p-4 transition-colors flex gap-3 items-start justify-between ${
                    !notif.read_at ? "bg-blue-50/30" : "bg-white"
                  }`}
                >
                  <div className="flex gap-3 items-start flex-1 min-w-0">
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

                  {!notif.read_at && (
                    <button
                      onClick={() => handleMarkAsRead(notif.id)}
                      title="Mark as read"
                      className="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors shrink-0 ml-2"
                    >
                      <Check className="w-4 h-4" />
                    </button>
                  )}
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
