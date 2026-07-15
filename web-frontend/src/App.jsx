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

import DoctorLayout from "./features/doctor-dashboard/components/DoctorLayout";
import HomePage from "./features/doctor-dashboard/pages/HomePage";
function App() {
  return (
    <Router>
      <Routes>
        
        <Route path="/" element={<OnboardingPage />} />
        <Route path="/login" element={<LoginPage />} />

        {/* admin routes */}
        <Route path="/app" element={<DashboardLayout />}>
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="doctors" element={<DoctorsManagementPage />} />
          <Route path="users" element={<UsersManagementPage />} />
          <Route path="audit-logs" element={<AuditLogsPage />} />
        </Route>

        {/* doctors routes*/}
        <Route path="/doctor" element={<DoctorLayout />}>
          <Route path="dashboard" element={<HomePage />} />
        </Route>

        <Route path="*" element={<Navigate to="/" />} />
      </Routes>
    </Router>
  );
}

export default App;
