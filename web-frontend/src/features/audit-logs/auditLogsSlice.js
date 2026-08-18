import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

export const fetchDashboardStats = createAsyncThunk(
  "auditLogs/fetchDashboardStats",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/admin/audit-logs/count");
      return response.data.data;
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  },
);

export const fetchDoctorRequestsCount = createAsyncThunk(
  "auditLogs/fetchDoctorRequestsCount",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/admin/doctor-requests/count");
      return response.data.data.count; 
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  },
);

export const fetchAuditLogs = createAsyncThunk(
  "auditLogs/fetchAuditLogs",
  async (filters = {}, { rejectWithValue }) => {
    try {
      
      const response = await api.get("/admin/audit-logs", { params: filters });
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  },
);

const auditLogsSlice = createSlice({
  name: "auditLogs",
  initialState: {
    logs: [],
    stats: {
      count: 0,
    },
    doctorRequestsCount: 0,
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchAuditLogs.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchAuditLogs.fulfilled, (state, action) => {
        state.loading = false;
        state.logs = action.payload.data;
      })
      .addCase(fetchAuditLogs.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload.data;
      })
      .addCase(fetchDashboardStats.fulfilled, (state, action) => {
        state.stats = action.payload;
      })
      .addCase(fetchDoctorRequestsCount.fulfilled, (state, action) => {
        state.doctorRequestsCount = action.payload; 
      });
  },
});

export default auditLogsSlice.reducer;
