import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

// جلب الإشعارات من الـ API
export const fetchNotifications = createAsyncThunk(
  "notifications/fetchNotifications",
  async (_, { rejectWithValue }) => {
    console.log(
      "Token right before fetching notifications:",
      localStorage.getItem("token"),
    );
    try {
      
      const response = await api.get("/admin/notifications");
      // بناءً على شكل الرد في البوستمان، الـ data غالباً تكون مصفوفة مباشرة أو داخل data.data
      return response.data.data || response.data;
    } catch (error) {
      // اطبعي الاستجابة الكاملة للخطأ القادم من السيرفر
      console.log("Error Response Object:", error.response);
      return rejectWithValue(
        error.response?.data?.message || "Failed to fetch notifications",
      );
    }
  },
  {
    // هذا الشرط يمنع إرسال طلب جديد إذا كان هنالك طلب جاري حالياً لمنع ازدواجية الطلبات
    condition: (_, { getState }) => {
      const { loading } = getState().notifications;
      if (loading) {
        return false;
      }
    },
  },
);

const notificationsSlice = createSlice({
  name: "notifications",
  initialState: {
    list: [],
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchNotifications.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchNotifications.fulfilled, (state, action) => {
        state.loading = false;
        state.list = action.payload;
      })
      .addCase(fetchNotifications.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export default notificationsSlice.reducer;
