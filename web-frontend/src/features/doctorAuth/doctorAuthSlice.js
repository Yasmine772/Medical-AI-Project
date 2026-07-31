import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

// Async thunk لتسجيل دخول الأطباء
export const loginDoctor = createAsyncThunk(
  "doctorAuth/loginDoctor",
  async ({ email, password }, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append("email", email.trim());
      formData.append("password", password);

      const response = await api.post("/doctor/login", formData, {
        headers: {
          Accept: "application/json",
        },
      });

      return response.data;
    } catch (error) {
      if (error.response) {
        return rejectWithValue({
          status: error.response.status,
          data: error.response.data,
        });
      }
      return rejectWithValue({ message: error.message });
    }
  },
);

// Thunk لتسجيل الخروج
export const logoutDoctor = createAsyncThunk(
  "doctorAuth/logoutDoctor",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.post("/doctor/logout");
      return response.data;
    } catch (error) {
      if (error.response) {
        return rejectWithValue(error.response.data);
      }
      return rejectWithValue({ message: error.message });
    }
  },
);

const doctorAuthSlice = createSlice({
  name: "doctorAuth",
  initialState: {
    doctorToken: null,
    doctorInfo: null,
    isAuthenticated: false,
    loading: false,
    error: null,
  },
  reducers: {
    doctorLogout: (state) => {
      state.doctorToken = null;
      state.doctorInfo = null;
      state.isAuthenticated = false;
      localStorage.removeItem("doctor_token");
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginDoctor.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginDoctor.fulfilled, (state, action) => {
        state.loading = false;
        // في حال تم تسجيل الدخول بنجاح وحصلنا على توكن
        const token = action.payload.data?.access_token;
        if (token) {
          state.doctorToken = token;
          state.isAuthenticated = true;
          localStorage.setItem("doctor_token", token);
        }
      })
      .addCase(logoutDoctor.fulfilled, (state) => {
        state.doctorToken = null;
        state.doctorInfo = null;
        state.isAuthenticated = false;
        localStorage.removeItem("doctor_token");
      })
      .addCase(loginDoctor.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { doctorLogout } = doctorAuthSlice.actions;
export default doctorAuthSlice.reducer;
