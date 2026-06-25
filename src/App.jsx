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

function App() {
  return (
    <Router>
      <Routes>
       
        <Route path="/" element={<LoginPage />} />

        
        <Route path="/app" element={<DashboardLayout />}>
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="doctors" element={<DoctorsManagementPage />} />
          <Route path="users" element={<UsersManagementPage />} />
        </Route>

        
        <Route path="*" element={<Navigate to="/" />} />
      </Routes>
    </Router>
  );
}

export default App;
