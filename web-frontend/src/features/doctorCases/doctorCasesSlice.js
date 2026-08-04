import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";
// 3. جلب إحصائيات الحالات للـ Stats Cards
export const fetchReviewStats = createAsyncThunk(
  "doctorCases/fetchReviewStats",
  async (_, thunkAPI) => {
    try {
      const response = await api.get("/doctor/reviews/stats");
      return response.data.data; // يعيد كائن الـ data الذي يحتوي على total, urgent, pending, completed
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const submitReview = createAsyncThunk(
  "doctorCases/submitReview",
  async ({ sessionHash, payload }, { rejectWithValue }) => {
    try {
      const response = await api.post(
        `/doctor/reviews/${sessionHash}/submit`,
        payload,
      );
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  },
);
// 1. جلب قائمة الحالات مع الفلاتر المتاحة
export const fetchDoctorReviews = createAsyncThunk(
  "doctorCases/fetchDoctorReviews",
  async ({ status, date_filter, language_code = "en" } = {}, thunkAPI) => {
    try {
      const params = {};
      // الفلاتر حسب الجدول تماماً: all | urgent | pending | completed
      if (status && status !== "all") params.status = status;
      // الفلاتر حسب الجدول تماماً: today | last_7_days | last_30_days
      if (date_filter) params.date_filter = date_filter;
      params.language_code = language_code;

      const response = await api.get("/doctor/reviews", { params });
      // الـ Backend يرجع الكائن الذي يحتوي على مفتاح data بداخله المصفوفة
      return response.data.data || [];
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  },
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
  },
);

const doctorCasesSlice = createSlice({
  name: "doctorCases",
  initialState: {
    casesList: [],
    currentCaseDetails: null,
    stats: null,
    loading: false,
    detailsLoading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
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
      })
      .addCase(submitReview.pending, (state) => {
        state.loading = true; // أو يمكنك تعريف حقل خاص مثل submittinLoading
      })
      .addCase(submitReview.fulfilled, (state) => {
        state.loading = false;
        // يمكننا هنا تحديث القائمة أو إعادة تعيين الحالة الحالية إذا لزم الأمر
      })
      .addCase(submitReview.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchReviewStats.fulfilled, (state, action) => {
        state.stats = action.payload;
      })
  },
});

export default doctorCasesSlice.reducer;
