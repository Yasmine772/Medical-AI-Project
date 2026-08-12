import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";
export const fetchApprovedDoctors = createAsyncThunk(
  "doctors/fetchApprovedDoctors",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("/admin/doctor-requests/approvedDoctors");
      return response.data.data; // بناءً على شكل استجابة الـ Postman
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  }
);

// حذف طبيب عبر الـ API (إذا وجد مسار للحذف، أو يمكن حذفه محلياً أو إضافته لاحقاً)
export const deleteDoctorApi = createAsyncThunk(
  "doctors/deleteDoctorApi",
  async (id, { rejectWithValue }) => {
    try {
      // ضع مسار الحذف الصحيح هنا إن وجد، مثلاً: await api.delete(`/admin/doctors/${id}`);
      return id; 
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  }
);

const doctorsSlice = createSlice({
  name: "doctors",
  initialState: {
    pending: [],
    approved: [],
    loading: false,
    error: null,
  },
  reducers: {
    approveDoctor: (state, action) => {
      const doctor = state.pending.find((d) => d.id === action.payload);
      if (doctor) {
        state.approved.push({ ...doctor, status: "Active" });
        state.pending = state.pending.filter((d) => d.id !== action.payload);
      }
    },
    rejectDoctor: (state, action) => {
      state.pending = state.pending.filter((d) => d.id !== action.payload);
    },
    deleteDoctor: (state, action) => {
      state.approved = state.approved.filter(
        (doc) => doc.id !== action.payload
      );
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchApprovedDoctors.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchApprovedDoctors.fulfilled, (state, action) => {
        state.loading = false;
        state.approved = action.payload;
      })
      .addCase(fetchApprovedDoctors.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(deleteDoctorApi.fulfilled, (state, action) => {
        state.approved = state.approved.filter(
          (doc) => doc.id !== action.payload
        );
      });
  },
});

export const { approveDoctor, rejectDoctor, deleteDoctor } = doctorsSlice.actions;
export default doctorsSlice.reducer;