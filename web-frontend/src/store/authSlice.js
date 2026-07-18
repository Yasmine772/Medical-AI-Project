import { createSlice } from "@reduxjs/toolkit";

const authSlice = createSlice({
  name: "auth",
  initialState: {
    token: localStorage.getItem("token") || null,
    isAuthenticated: !!localStorage.getItem("token"),
    userEmail: localStorage.getItem("email") || "",
    userName: localStorage.getItem("userName") || "Dr. Sarah Chen",
    userImage: localStorage.getItem("userImage") || null,
  },
  reducers: {
    updateProfile: (state, action) => {
      state.userName = action.payload.name;
      state.userImage = action.payload.image; // قد تكون null أو رابط صورة

      // حفظ في الـ localStorage لضمان بقائها حتى لو تم إغلاق الـ session
      localStorage.setItem("userName", action.payload.name);
      if (action.payload.image) {
        localStorage.setItem("userImage", action.payload.image);
      }
    },
    loginSuccess: (state, action) => {
      // action.payload هو { token, email }
      const { token, email } = action.payload;

      if (token) {
        state.token = token;
        state.userEmail = email;
        state.isAuthenticated = true;

        // تخزين التوكن كـ نص (String) فقط
        localStorage.setItem("token", token);
        // تخزين الإيميل كـ نص (String)
        localStorage.setItem("email", email);
      }
    },

    initialState: {
      // عند إعادة تحميل الصفحة، استرجعي التوكن والإيميل بشكل منفصل
      token: localStorage.getItem("token") || null,
      userEmail: localStorage.getItem("email") || null,
      isAuthenticated: !!localStorage.getItem("token"),
    },

    logout: (state) => {
      state.token = null;
      state.userEmail = null;
      state.isAuthenticated = false;
      localStorage.removeItem("token");
      localStorage.removeItem("persist:root");
      // clean local storage to ensure no persisted state remains
      window.location.reload();
    },
  },
});

export const { loginSuccess, logout, updateProfile } = authSlice.actions;
export default authSlice.reducer;
