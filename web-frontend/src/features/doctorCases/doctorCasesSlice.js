import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

// 1. جلب قائمة الحالات مع الفلاتر
export const fetchDoctorReviews = createAsyncThunk(
  "doctorCases/fetchDoctorReviews",
  async ({ status, date_filter, language_code = "en" } = {}, thunkAPI) => {
    try {
      const params = {};
      if (status && status !== "all") params.status = status;
      if (date_filter) params.date_filter = date_filter;
      params.language_code = language_code;

      const response = await api.get("/doctor/reviews", { params });
      return response.data; // مصفوفة الحالات أو الكائن حسب شكل الرد من الـ backend
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  }
);

// 2. جلب تفاصيل حالة محددة عند المراجعة
export const fetchCaseDetails = createAsyncThunk(
  "doctorCases/fetchCaseDetails",
  async (sessionHash, thunkAPI) => {
    try {
      const response = await api.get(`/doctor/reviews/${sessionHash}`);
      return response.data;
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  }
);

const doctorCasesSlice = createSlice({
  name: "doctorCases",
  initialState: {
    casesList: [],
    currentCaseDetails: null,
    loading: false,
    detailsLoading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      // حالات جلب القائمة
      .addCase(fetchDoctorReviews.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDoctorReviews.fulfilled, (state, action) => {
        state.loading = false;
        state.casesList = action.payload;
      })
      .addCase(fetchDoctorReviews.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })

      // حالات جلب تفاصيل الحالة المحددة
      .addCase(fetchCaseDetails.pending, (state) => {
        state.detailsLoading = true;
      })
      .addCase(fetchCaseDetails.fulfilled, (state, action) => {
        state.detailsLoading = false;
        state.currentCaseDetails = action.payload;
      })
      .addCase(fetchCaseDetails.rejected, (state, action) => {
        state.detailsLoading = false;
        state.error = action.payload;
      });
  },
});

export default doctorCasesSlice.reducer;