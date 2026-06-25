import { useState } from "react"; 
import { Outlet } from "react-router-dom";
import Sidebar from "./Sidebar";
import LogoutModal from "../../auth/components/LogoutModal";
import ConfirmationModal from "../../doctors/components/ConfirmationModal"; 
const DashboardLayout = () => {
  const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);
  const [actionModal, setActionModal] = useState({
    isOpen: false,
    type: "",
    onConfirm: null, 
  });

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
      <Sidebar onLogoutClick={() => setIsLogoutModalOpen(true)} />

      
      <main className="flex-1 h-full overflow-y-auto p-6">
        <Outlet context={{ setActionModal }} />
      </main>

      
      <LogoutModal
        isOpen={isLogoutModalOpen}
        onClose={() => setIsLogoutModalOpen(false)}
        onConfirm={() => {
          window.location.href = "/login";
        }}
      />

      <ConfirmationModal
        isOpen={actionModal.isOpen}
        onClose={() => setActionModal({ ...actionModal, isOpen: false })}
        onConfirm={() => {
          if (actionModal.onConfirm) actionModal.onConfirm();
          setActionModal({ ...actionModal, isOpen: false });
        }}
        title={`Confirm ${actionModal.type}`}
        message={`Are you sure you want to ${actionModal.type} this request?`}
      />
    </div>
  );
};
export default DashboardLayout;
