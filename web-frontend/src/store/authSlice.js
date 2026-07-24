import { createSlice } from "@reduxjs/toolkit";

const authSlice = createSlice({
  name: "auth",
  initialState: {
    token: localStorage.getItem("token") || null,
    isAuthenticated: !!localStorage.getItem("token"),
    userEmail: localStorage.getItem("email") || "",
    userName: localStorage.getItem("full_name") || "Dr. Sarah Chen",
    userImage: localStorage.getItem("userImage") || null,
    userRole: localStorage.getItem("userRole") || "",
    birthDate: localStorage.getItem("birthDate") || null, // عدل هذه
    gender: localStorage.getItem("gender") || null, // عدل هذه
    age: localStorage.getItem("age") || null,
  },
  reducers: {
    updateProfile: (state, action) => {
      state.userName = action.payload.name;
      state.userImage = action.payload.image; // قد تكون null أو رابط صورة
      state.userRole = action.payload.role || state.userRole;
      state.birthDate = action.payload.birthDate;
      state.gender = action.payload.gender;
      state.age = action.payload.age;
      // حفظ في الـ localStorage لضمان بقائها حتى لو تم إغلاق الـ session
      if (action.payload.name)
        localStorage.setItem("full_name", action.payload.name);
      if (action.payload.birthDate)
        localStorage.setItem("birthDate", action.payload.birthDate);
      if (action.payload.gender)
        localStorage.setItem("gender", action.payload.gender);
      if (action.payload.age) localStorage.setItem("age", action.payload.age);
      if (action.payload.image)
        localStorage.setItem("userImage", action.payload.image);
    },
    loginSuccess: (state, action) => {
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

    logout: (state) => {
      state.token = null;
      state.userEmail = null;
      state.userRole = "";
      state.isAuthenticated = false;
      localStorage.removeItem("token");
      localStorage.removeItem("persist:root");
      localStorage.removeItem("userRole");
      // أضف هذه السطور هنا:
      localStorage.removeItem("full_name");
      localStorage.removeItem("birthDate");
      localStorage.removeItem("gender");
      localStorage.removeItem("age");
      localStorage.removeItem("userImage");
      localStorage.removeItem("email");
      // clean local storage to ensure no persisted state remains
      window.location.reload();
    },
  },
});

export const { loginSuccess, logout, updateProfile } = authSlice.actions;
export default authSlice.reducer;
