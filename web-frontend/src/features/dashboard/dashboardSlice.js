import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

export const fetchDashboardStats = createAsyncThunk(
  "dashboard/fetchStats",
  async () => {
    const [
      users,
      diagnoses,
      doctors,
      content,
      patientTypes,
      sessionStatus,
      topDiseases,
      dateRes,
    ] = await Promise.all([
      api.get("/admin/dashboard/user-active-count"),
      api.get("/admin/dashboard/daily-diagnoses-count"),
      api.get("/admin/dashboard/doctor-active-count"),
      api.get("/admin/dashboard/new-content-items-count"),
      api.get("/admin/dashboard/type-of-patient-count"),
      api.get("/admin/dashboard/diagnosis-sessions-status-count"),
      api.get("/admin/dashboard/top-specialties-by-diagnoses"),
      api.get("/admin/dashboard/current-date"),
    ]);

    return {
      activeUsers: users.data.data.count,
      dailyDiagnoses: diagnoses.data.data.count,
      activeDoctors: doctors.data.data.count.active_doctors,
      newContentItems: content.data.data.count,
      patientStats: {
        now: patientTypes.data.data.now_patients,
        regular: patientTypes.data.data.regular_patients,
      },
      sessionStatus: {
        active: sessionStatus.data.data.active , 
        completed: sessionStatus.data.data.completed ,
        pending: sessionStatus.data.data.pending ,
      },
      topDiseases: topDiseases.data.data || [],
      currentDate: dateRes.data,
    };
  },
);

const dashboardSlice = createSlice({
  name: "dashboard",
  initialState: { stats: null, loading: false },
  extraReducers: (builder) => {
    builder
      .addCase(fetchDashboardStats.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchDashboardStats.fulfilled, (state, action) => {
        state.loading = false;
        state.stats = action.payload;
      });
  },
});

export default dashboardSlice.reducer;
