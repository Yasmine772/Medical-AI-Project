import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

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
      });
  },
});

export default doctorDashboardSlice.reducer;
