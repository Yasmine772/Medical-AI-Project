import { useEffect, useState} from 'react';
import { useDispatch } from 'react-redux'; 
import echo from './echo';         
import api from './api/axios';
import { fetchNotifications, fetchUnreadCount } from './features/notifications/notificationsSlice'; 
import { getFCMToken, listenForMessages } from './firebase';
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
import DoctorCasesPage from "./features/doctorCases/pages/DoctorCasesPage";

function App() {
    const dispatch = useDispatch();
    const [userId, setUserId] = useState(() => {
    const storedUserId = localStorage.getItem('user_id');
    return storedUserId ? parseInt(storedUserId) : null;
  });

    useEffect(() => {
        if (userId) return; 

          api.get('/api/user')
              .then(response => {
                  setUserId(response.data.id);
                  localStorage.setItem('user_id', response.data.id);
              })
              .catch(() => console.log('User not logged in'));
      }, [userId]); 

      useEffect(() => {
         if (userId){
            console.log(' Getting FCM Token...');
            getFCMToken().then(token => {
                if (token) {
                    console.log(' FCM Token saved:', token);
                    listenForMessages(); 
                }
            });
        }
    }, [userId]);

    useEffect(() => {
        if (!userId) {
            console.log('Waiting for user ID...');
            return;
        }

        console.log(`Listening for Reverb notifications on user.${userId}...`);

        try {
            const channel = echo.private(`user.${userId}`);

            channel.listen('.notification.received', (e) => {
                console.log('New notification:', e);

                dispatch(fetchUnreadCount());
                dispatch(fetchNotifications());
            });

            setTimeout(() => {
                if (echo.connector && echo.connector.socket) {
                    echo.connector.socket.on('connect', () => {
                        console.log('WebSocket connected successfully!');
                    });

                    echo.connector.socket.on('disconnect', () => {
                        console.log('WebSocket disconnected!');
                    });
                }
            }, 1000);

            return () => {
                try {
                    echo.leaveChannel(`user.${userId}`);
                    console.log(` Left channel user.${userId}`);
                    //  isSubscribed.current = false; 
                } catch (err) {
                    console.log('Error leaving channel:', err);
                }
            };
        } catch (error) {
            console.log(' WebSocket error (non-critical):', error);
        }
    }, [userId]);

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
          <Route path="cases" element={<DoctorCasesPage />} />
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
