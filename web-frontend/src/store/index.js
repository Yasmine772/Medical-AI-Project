import { configureStore, combineReducers } from "@reduxjs/toolkit";
import {
  persistReducer,
  persistStore,
  FLUSH,
  REHYDRATE,
  PAUSE,
  PERSIST,
  PURGE,
  REGISTER,
} from "redux-persist";
import authReducer from "./authSlice";
import dashboardReducer from "../features/dashboard/dashboardSlice";
import joiningRequestsReducer from "../features/joiningRequests/joiningRequestsSlice";
const storage = {
  getItem: (key) => Promise.resolve(localStorage.getItem(key)),
  setItem: (key, value) => Promise.resolve(localStorage.setItem(key, value)),
  removeItem: (key) => Promise.resolve(localStorage.removeItem(key)),
};
import doctorsReducer from "../features/doctors/doctorsSlice";
import usersReducer from "../features/users/usersSlice";
import auditLogsReducer from "../features/audit-logs/auditLogsSlice";
import doctorRequestsReducer from "../features/doctors/doctorRequestsSlice";
import notificationsReducer from "../features/notifications/notificationsSlice";
import doctorDashboardReducer from "../features/doctor-dashboard/doctorDashboardSlice";
const persistConfig = {
  key: "root",
  storage,
  whitelist: [
    "doctors",
    "users",
    "auth",
    "joiningRequests",
    "doctorRequests",
    "notifications",
  ],
};

const persistedReducer = persistReducer(
  persistConfig,
  combineReducers({
    doctors: doctorsReducer,
    auth: authReducer,

    users: usersReducer,
    auditLogs: auditLogsReducer,
    dashboard: dashboardReducer,
    joiningRequests: joiningRequestsReducer,
    doctorRequests: doctorRequestsReducer,
    notifications: notificationsReducer,
    doctorDashboard: doctorDashboardReducer,
  }),
);

export const store = configureStore({
  reducer: persistedReducer,
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
      },
    }),
});

export const persistor = persistStore(store);
