import toast from "react-hot-toast";
import { useState } from "react";
import { Outlet, useNavigate } from "react-router-dom";
import { useDispatch } from "react-redux";
import DoctorSidebar from "./DoctorSidebar";
import LogoutModal from "../../auth/components/LogoutModal";
import ProfilePanel from "./ProfilePanel";
import { logoutDoctor, doctorLogout } from "../../doctorAuth/doctorAuthSlice";
const DoctorLayout = () => {
  const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const handleLogoutConfirm = async () => {
    try {
      // استدعاء الـ API الخاص بتسجيل الخروج من الباك إند
      const resultAction = await dispatch(logoutDoctor());

      if (logoutDoctor.fulfilled.match(resultAction)) {
        // إظهار الرسالة القادمة من الاستجابة (User logout successfully)
        const message =
          resultAction.payload?.message || "User logout successfully";
        toast.success(message, {
          duration: 3000, // المدة بالميلي ثانية (3 ثواني)
          style: {
            background: "#72A6BB",
            color: "#fff",
            borderRadius: "12px",
          },
        });
      }
    } catch (error) {
      console.error("Logout error:", error);
    } finally {
      // تفريغ بيانات التوكن محلياً والتوجه لصفحة تسجيل دخول الأطباء الصحيحة
      dispatch(doctorLogout());
      setIsLogoutModalOpen(false);
      navigate("/loginDoctor");
    }
  };

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
        onConfirm={handleLogoutConfirm}
      />

      <ProfilePanel
        isOpen={isProfileOpen}
        onClose={() => setIsProfileOpen(false)}
      />
    </div>
  );
};

export default DoctorLayout;
