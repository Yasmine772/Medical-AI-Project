import {
  BrowserRouter as Router,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";
import LoginPage from "./features/auth/pages/LoginPage";
import DoctorsManagementPage from "./features/doctors/pages/DoctorsManagementPage";
import DashboardPage from "./features/dashboard/pages/DashboardPage";
import DashboardLayout from "./features/dashboard/components/DashboardLayout";
import UsersManagementPage from "./features/users/pages/UsersManagementPage";
import OnboardingPage from "./features/auth/pages/OnboardingPage";
import AuditLogsPage from "./features/audit-logs/pages/AuditLogsPage";
import DiseasesPage from "./features/diseases/pages/DiseasesPage";
import DoctorLayout from "./features/doctor-dashboard/components/DoctorLayout";
import HomePage from "./features/doctor-dashboard/pages/HomePage";
import OtpVerification from "./features/auth/pages/OtpVerification";
import ForgotPassword from "./features/auth/pages/ForgotPassword";
import OtpVerificationPass from "./features/auth/pages/OtpVerificationPass";
import ResetPassword from "./features/auth/pages/ResetPassword";
import JoiningRequestsPage from "./features/joiningRequests/pages/JoiningRequestsPage";
import DoctorLoginPage from "./features/doctorAuth/pages/DoctorLoginPage";
import DoctorVerifyOtp from "./features/doctorAuth/pages/DoctorVerifyOtp";
import DoctorForgotPassword from "./features/doctorAuth/pages/DoctorForgotPassword";
import DoctorVerifyResetOtp from "./features/doctorAuth/pages/DoctorVerifyResetOtp";
import DoctorResetPassword from "./features/doctorAuth/pages/DoctorResetPassword";
function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<OnboardingPage />} />
        <Route path="/login" element={<LoginPage />} />

        <Route path="/otp-verification" element={<OtpVerification />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route
          path="/otp-verification-pass"
          element={<OtpVerificationPass />}
        />
        <Route path="/reset-password" element={<ResetPassword />} />
        {/* admin routes */}
        <Route path="/app" element={<DashboardLayout />}>
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="doctors" element={<DoctorsManagementPage />} />
          <Route path="users" element={<UsersManagementPage />} />
          <Route path="audit-logs" element={<AuditLogsPage />} />
          <Route path="/app/diseases" element={<DiseasesPage />} />
        </Route>

        {/* doctors routes*/}
        <Route path="/Layout" element={<DoctorLayout />}>
          <Route path="dashboard" element={<HomePage />} />
        </Route>
        <Route path="/loginDoctor" element={<DoctorLoginPage />} />
        <Route path="/otp-verification-doctor" element={<DoctorVerifyOtp />} />
        <Route
          path="/forgot-password-doctor"
          element={<DoctorForgotPassword />}
        />
        <Route
          path="/verify-reset-otp-doctor"
          element={<DoctorVerifyResetOtp />}
        />
        <Route
          path="/reset-password-doctor"
          element={<DoctorResetPassword />}
        />
        {/* joining requests */}
        <Route path="/joining-requests" element={<JoiningRequestsPage />} />

        <Route path="*" element={<Navigate to="/" />} />
      </Routes>
    </Router>
  );
}

export default App;
