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
const storage = {
  getItem: (key) => Promise.resolve(localStorage.getItem(key)),
  setItem: (key, value) => Promise.resolve(localStorage.setItem(key, value)),
  removeItem: (key) => Promise.resolve(localStorage.removeItem(key)),
};
import doctorsReducer from "../features/doctors/doctorsSlice";
import usersReducer from "../features/users/usersSlice";
import auditLogsReducer from "../features/audit-logs/auditLogsSlice";

const persistConfig = {
  key: "root",
  storage,
  whitelist: ["doctors", "users","auth"],
};

const persistedReducer = persistReducer(
  persistConfig,
  combineReducers({
    doctors: doctorsReducer,
    auth: authReducer,
    users: usersReducer,
    auditLogs: auditLogsReducer,
    dashboard: dashboardReducer,
    
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
