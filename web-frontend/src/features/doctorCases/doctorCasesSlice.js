import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";
import toast from "react-hot-toast";


export const fetchPdfReport = createAsyncThunk(
  "doctorCases/fetchPdfReport",
  async (sessionHash, thunkAPI) => {
    toast("Opening PDF report...", { icon: "📄" });

    try {
      const response = await api.get(`/doctor/reviews/${sessionHash}/pdf`, {
        responseType: "blob",
      });

      
      const blob = new Blob([response.data], { type: "application/pdf" });
      const pdfUrl = URL.createObjectURL(blob);
      window.open(pdfUrl, "_blank");

      return pdfUrl;
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const fetchReviewStats = createAsyncThunk(
  "doctorCases/fetchReviewStats",
  async (_, thunkAPI) => {
    try {
      const response = await api.get("/doctor/reviews/stats");
      return response.data.data; 
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

export const fetchDoctorReviews = createAsyncThunk(
  "doctorCases/fetchDoctorReviews",
  async ({ status, date_filter, language_code = "en" } = {}, thunkAPI) => {
    try {
      const params = {};
      
      if (status && status !== "all") params.status = status;
    
      if (date_filter) params.date_filter = date_filter;
      params.language_code = language_code;

      const response = await api.get("/doctor/reviews", { params });
   
      return response.data.data || [];
    } catch (error) {
      return thunkAPI.rejectWithValue(error.response?.data || error.message);
    }
  },
);


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
    pdfLoading: false,
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
        state.loading = true; 
      })
      .addCase(submitReview.fulfilled, (state) => {
        state.loading = false;
       
      })
      .addCase(submitReview.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchReviewStats.fulfilled, (state, action) => {
        state.stats = action.payload;
      })
      .addCase(fetchPdfReport.pending, (state) => {
        state.pdfLoading = true;
        state.error = null;
      })
      .addCase(fetchPdfReport.fulfilled, (state) => {
        state.pdfLoading = false;
      })
      .addCase(fetchPdfReport.rejected, (state, action) => {
        state.pdfLoading = false;
        state.error = action.payload;
      });
  },
});

export default doctorCasesSlice.reducer;
