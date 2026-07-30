import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import axios from "axios";


const joinApi = axios.create({
  baseURL: "", 
  withCredentials: true,
  headers: {
    Accept: "application/json",
  },
});

joinApi.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    const cleanToken = token.replace(/['"]+/g, "").trim();
    config.headers.Authorization = `Bearer ${cleanToken}`;
  }
  return config;
});


export const sendJoinRequest = createAsyncThunk(
  "joiningRequests/sendJoinRequest",
  async (formData, { rejectWithValue }) => {
    try {
      const response = await joinApi.post("/doctor/sendJoinRequest", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || "حدث خطأ ما أثناء إرسال الطلب",
      );
    }
  },
);

const joiningRequestsSlice = createSlice({
  name: "joiningRequests",
  initialState: {
    loading: false,
    successMessage: null,
    error: null,
  },
  reducers: {
    clearStatus: (state) => {
      state.loading = false;
      state.successMessage = null;
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(sendJoinRequest.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.successMessage = null;
      })
      .addCase(sendJoinRequest.fulfilled, (state, action) => {
        state.loading = false;
        state.successMessage =
          action.payload.message || "تم إرسال الطلب بنجاح!";
      })
      .addCase(sendJoinRequest.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { clearStatus } = joiningRequestsSlice.actions;
export default joiningRequestsSlice.reducer;
