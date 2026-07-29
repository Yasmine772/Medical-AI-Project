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

// (Async Thunk)
// في ملف auditLogsSlice.js
export const fetchAuditLogs = createAsyncThunk(
  "auditLogs/fetchAuditLogs",
  async (filters = {}, { rejectWithValue }) => {
    try {
      // إرسال الفلاتر كـ params مع الطلب
      const response = await api.get("/admin/audit-logs", { params: filters });
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  }
);

const auditLogsSlice = createSlice({
  name: "auditLogs",
  initialState: {
    logs: [],
    stats: { 
      count: 0, 
      data_changes: 0, 
      doctor_requests: 0, 
      sent_notifications: 0 
    },
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
      });
  },
});

export default auditLogsSlice.reducer;
