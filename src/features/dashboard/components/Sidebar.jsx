import { Link, useLocation } from "react-router-dom";
import {
  LayoutDashboard,
  Stethoscope,
  UserCog,
  HeartPulse,
  MessageSquare,
  LogOut,
} from "lucide-react";


const Sidebar = ({ onLogoutClick }) => {
  const location = useLocation();

  const menuItems = [
    { icon: LayoutDashboard, path: "/app/dashboard" },
    { icon: Stethoscope, path: "/app/doctors" },
    { icon: UserCog, path: "/app/users" },
    { icon: HeartPulse, path: "/app/patients" },
    { icon: MessageSquare, path: "/app/messages" },
    { icon: LogOut, path: "logout" },
  ];

  // داخل Sidebar.jsx
  return (
    <div className="w-20 bg-[#72A6BB]/60 backdrop-blur-xl border border-white/20 min-h-[90vh] flex flex-col items-center py-6 rounded-[32px]">
      {/* نضع الأيقونات الأساسية في الأعلى */}
      <div className="flex flex-col gap-6 w-full items-center">
        {menuItems.slice(0, -1).map((item, index) => {
          // كل العناصر ما عدا الأخير
          const isActive = location.pathname === item.path;
          return (
            <Link
              key={index}
              to={item.path}
              className={`p-3 rounded-xl transition-all duration-300 ${
                isActive
                  ? "bg-white text-[#72A6BB] shadow-lg scale-110"
                  : "text-white hover:bg-white/20"
              }`}
            >
              <item.icon size={24} />
            </Link>
          );
        })}
      </div>

      
      <div className="mt-auto">
        <button
          onClick={onLogoutClick}
          className="p-3 rounded-xl transition-all duration-300 text-white hover:bg-red-500/50 hover:scale-110"
        >
          <LogOut size={24} />
        </button>
      </div>
    </div>
  );
};

export default Sidebar;
