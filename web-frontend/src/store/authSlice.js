import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../api/axios";
export const fetchDoctorProfile = createAsyncThunk(
  "auth/fetchDoctorProfile",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/doctor/profile");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const updateDoctorProfile = createAsyncThunk(
  "auth/updateDoctorProfile",
  async (formData, { rejectWithValue }) => {
    try {
      formData.append("_method", "PATCH");
      const response = await api.post("/doctor/profile", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data.data;
    } catch (error) {
      console.error("Validation Errors:", error.response?.data);
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

const authSlice = createSlice({
  name: "auth",
  initialState: {
    token: localStorage.getItem("token") || null,
    isAuthenticated: !!localStorage.getItem("token"),
    userEmail: localStorage.getItem("email") || "",
    userName: localStorage.getItem("full_name") || "Dr. Sarah Chen",
    userImage: localStorage.getItem("userImage") || null,
    userRole: localStorage.getItem("role") || "",
    birthDate: localStorage.getItem("birthDate") || null,
    gender: localStorage.getItem("gender") || null,
    age: localStorage.getItem("age") || null,
  },
  reducers: {
    updateProfile: (state, action) => {
      state.userName = action.payload.name;
      state.userImage = action.payload.image;
      state.userRole = action.payload.role || state.userRole;
      state.birthDate = action.payload.birthDate;
      state.gender = action.payload.gender;
      state.age = action.payload.age;

      if (action.payload.name)
        localStorage.setItem("full_name", action.payload.name);
      if (action.payload.birthDate)
        localStorage.setItem("birthDate", action.payload.birthDate);
      if (action.payload.gender)
        localStorage.setItem("gender", action.payload.gender);
      if (action.payload.age) localStorage.setItem("age", action.payload.age);
      if (action.payload.image)
        localStorage.setItem("userImage", action.payload.image);
      if (action.payload.role)
        localStorage.setItem("role", action.payload.role);
    },
    loginSuccess: (state, action) => {
      const { token, email, role } = action.payload;

      if (token) {
        state.token = token;
        state.userEmail = email;
        state.userRole = role;
        state.isAuthenticated = true;

        localStorage.setItem("token", token);
        localStorage.setItem("email", email);
        localStorage.setItem("role", role);
      }
    },
    logout: (state) => {
      state.token = null;
      state.userEmail = null;
      state.userRole = "";
      state.isAuthenticated = false;
      state.doctorProfile = null;

      // تنظيف LocalStorage بالكامل
      localStorage.clear();
      // window.location.reload();
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchDoctorProfile.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDoctorProfile.fulfilled, (state, action) => {
        state.loading = false;
        state.doctorProfile = action.payload;
      })
      .addCase(fetchDoctorProfile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(updateDoctorProfile.fulfilled, (state, action) => {
        state.loading = false;
        state.doctorProfile = action.payload;
      });
  },
});

export const { loginSuccess, logout, updateProfile } = authSlice.actions;
export default authSlice.reducer;
