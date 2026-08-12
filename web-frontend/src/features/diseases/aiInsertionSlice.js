import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import aiApi from "../../api/aiApi";

export const insertJsonFile = createAsyncThunk(
  "aiInsertion/insertJsonFile",
  async (fileData, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append("file", fileData); // المفتاح هو file بناءً على بوستمان

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
// export const insertJsonFile = createAsyncThunk(
//   "aiInsertion/insertJsonFile",
//   async (_, { rejectWithValue }) => { // أزلنا استقبال الـ file مؤقتاً
//     try {
//       const response = await aiApi.post("/insert/json-file", {
//         test: "ping_server" // نرسل بيانات جيسون عادية للتجربة
//       });
//       return response.data;
//     } catch (error) {
//       return rejectWithValue(error.response?.data || error.message);
//     }
//   }
// );

// Thunk لرفع ملف الـ PDF
export const insertPdfFile = createAsyncThunk(
  "aiInsertion/insertPdfFile",
  async (fileData, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append("file", fileData); // المفتاح هو file بناءً على بوستمان

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
        state.successMessage = "تم رفع ملف الـ JSON بنجاح!";
      })
      .addCase(insertJsonFile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || "حدث خطأ أثناء رفع ملف الـ JSON";
      })
      // PDF File Cases
      .addCase(insertPdfFile.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.successMessage = null;
      })
      .addCase(insertPdfFile.fulfilled, (state) => {
        state.loading = false;
        state.successMessage = "تم رفع ملف الـ PDF بنجاح!";
      })
      .addCase(insertPdfFile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || "حدث خطأ أثناء رفع ملف الـ PDF";
      });
  },
});

export const { clearMessages } = aiInsertionSlice.actions;
export default aiInsertionSlice.reducer;
