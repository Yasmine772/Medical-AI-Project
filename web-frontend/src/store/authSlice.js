import { createSlice } from "@reduxjs/toolkit";

const authSlice = createSlice({
  name: "auth",
  initialState: {
    token: localStorage.getItem("token") || null,
    isAuthenticated: !!localStorage.getItem("token"),
  },
  reducers: {
    loginSuccess: (state, action) => {
      console.log("Token received in slice:", action.payload); // أضيفي هذا السطر للـ debug
      if (action.payload) {
        state.token = action.payload;
        state.isAuthenticated = true;
        localStorage.setItem("token", action.payload);
      }
    },
    // في authSlice.js
    logout: (state) => {
      state.token = null;
      state.isAuthenticated = false;
      localStorage.removeItem("token");
      // مسح بيانات الـ persist الخاصة بـ doctors
      localStorage.removeItem("persist:root");
      // إجبار المتصفح على إعادة التحميل لمسح ذاكرة الـ Redux
      window.location.reload();
    },
  },
});

export const { loginSuccess, logout } = authSlice.actions;
export default authSlice.reducer;
