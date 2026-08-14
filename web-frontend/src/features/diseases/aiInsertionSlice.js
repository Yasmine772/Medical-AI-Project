import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import aiApi from "../../api/aiApi";

export const insertJsonFile = createAsyncThunk(
  "aiInsertion/insertJsonFile",
  async (fileData, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append("file", fileData); 

      const response = await aiApi.post("/insert/json-file", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

export const insertPdfFile = createAsyncThunk(
  "aiInsertion/insertPdfFile",
  async (fileData, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append("file", fileData);

      const response = await aiApi.post("/insert/pdf", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || error.message);
    }
  },
);

const aiInsertionSlice = createSlice({
  name: "aiInsertion",
  initialState: {
    loading: false,
    successMessage: null,
    error: null,
  },
  reducers: {
    clearMessages: (state) => {
      state.loading = false;
      state.successMessage = null;
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // JSON File Cases
      .addCase(insertJsonFile.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.successMessage = null;
      })
      .addCase(insertJsonFile.fulfilled, (state) => {
        state.loading = false;
        state.successMessage = "json file sent successfuly";
      })
      .addCase(insertJsonFile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || "something went wrong";
      })
      // PDF File Cases
      .addCase(insertPdfFile.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.successMessage = null;
      })
      .addCase(insertPdfFile.fulfilled, (state) => {
        state.loading = false;
        state.successMessage = "PDF file sent successfuly";
      })
      .addCase(insertPdfFile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || "something went wrong";
      });
  },
});

export const { clearMessages } = aiInsertionSlice.actions;
export default aiInsertionSlice.reducer;
