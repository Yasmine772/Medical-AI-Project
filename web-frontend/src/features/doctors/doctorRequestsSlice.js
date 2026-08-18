import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios"; 

export const fetchDoctorRequests = createAsyncThunk(
  "doctorRequests/fetchDoctorRequests",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/admin/doctor-requests");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || "Failed to fetch doctor requests");
    }
  }
);

const doctorRequestsSlice = createSlice({
  name: "doctorRequests",
  initialState: {
    requests: [],
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchDoctorRequests.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDoctorRequests.fulfilled, (state, action) => {
        state.loading = false;
        state.requests = action.payload;
      })
      .addCase(fetchDoctorRequests.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export default doctorRequestsSlice.reducer;