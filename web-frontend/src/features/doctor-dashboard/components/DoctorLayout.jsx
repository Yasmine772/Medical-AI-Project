import { useState } from "react";
import { Outlet } from "react-router-dom";
import DoctorSidebar from "./DoctorSidebar";
import LogoutModal from "../../auth/components/LogoutModal";
import ProfilePanel from "./ProfilePanel";

const DoctorLayout = () => {
  const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  return (
    <div
      className="flex h-screen w-screen overflow-hidden p-4 gap-4"
      style={{
        backgroundImage: `url('/الخلفية.png')`,
        backgroundSize: "cover",
        backgroundPosition: "center",
        backgroundRepeat: "no-repeat",
      }}
    >
      <DoctorSidebar
        onLogoutClick={() => setIsLogoutModalOpen(true)}
        onProfileClick={() => setIsProfileOpen(true)}
      />

      <main className="flex-1 h-full overflow-y-auto p-6">
        <Outlet />
      </main>

      <LogoutModal
        isOpen={isLogoutModalOpen}
        onClose={() => setIsLogoutModalOpen(false)}
        onConfirm={() => {
          window.location.href = "/login";
        }}
      />

      <ProfilePanel
        isOpen={isProfileOpen}
        onClose={() => setIsProfileOpen(false)}
      />
    </div>
  );
};

export default DoctorLayout;
