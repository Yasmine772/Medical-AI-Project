import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

export const fetchDoctorSchedules = createAsyncThunk(
  "doctorDashboard/fetchDoctorSchedules",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/doctor/schedules");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const updateDoctorScheduleItem = createAsyncThunk(
  "doctorDashboard/updateDoctorScheduleItem",
  async ({ id, scheduleData }, { rejectWithValue }) => {
    try {
      const response = await api.patch(`/doctor/schedules/${id}`, scheduleData);
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const fetchDoctorSummary = createAsyncThunk(
  "doctorDashboard/fetchDoctorSummary",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/doctor/summary");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);
export const updateAvailability = createAsyncThunk(
  "doctorDashboard/updateAvailability",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.patch("/doctor/availability");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

const doctorDashboardSlice = createSlice({
  name: "doctorDashboard",
  initialState: {
    summary: null,
    schedules: [],
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchDoctorSummary.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDoctorSummary.fulfilled, (state, action) => {
        state.loading = false;
        state.summary = action.payload;
      })
      .addCase(fetchDoctorSummary.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(updateAvailability.fulfilled, (state) => {
        if (state.summary) {
          state.summary.is_available = state.summary.is_available === 1 ? 0 : 1;
        }
      })
      .addCase(fetchDoctorSchedules.fulfilled, (state, action) => {
        state.schedules = action.payload;
      })
      .addCase(updateDoctorScheduleItem.fulfilled, (state, action) => {
        // تحديث العنصر المعدل داخل المصفوفة بناءً على الـ id
        const index = state.schedules.findIndex(
          (s) => s.id === action.payload.id,
        );
        if (index !== -1) {
          state.schedules[index] = action.payload;
        } else {
          state.schedules.push(action.payload);
        }
      });
  },
});

export default doctorDashboardSlice.reducer;
