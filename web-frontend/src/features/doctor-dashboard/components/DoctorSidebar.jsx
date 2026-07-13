import { Link, useLocation } from "react-router-dom";
import { LayoutDashboard, User, LogOut } from "lucide-react";

const DoctorSidebar = ({ onLogoutClick, onProfileClick }) => {

  const location = useLocation();

  return (
    <div className="w-20 bg-[#72A6BB]/60 backdrop-blur-xl border border-white/20 min-h-[90vh] flex flex-col items-center py-6 rounded-[32px]">
      <div className="flex flex-col gap-6 w-full items-center">
        <Link
          to="/doctor/dashboard"
          className={`p-3 rounded-xl transition-all duration-300 ${
            location.pathname === "/doctor/dashboard"
              ? "bg-white text-[#72A6BB] shadow-lg scale-110"
              : "text-white hover:bg-white/20"
          }`}
        >
          <LayoutDashboard size={24} />
        </Link>

        
        <button
          onClick={onProfileClick} 
          className="p-3 rounded-xl transition-all duration-300 text-white hover:bg-white/20"
        >
          <User size={24} />
        </button>
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
export default DoctorSidebar;
