import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";

import api from "../../api/axios"; // استيراد الـ Axios instance مع الـ interceptor

// 1. إنشاء الـ Thunk لجلب البيانات
export const fetchUsers = createAsyncThunk("users/fetchUsers", async () => {
  console.log("Debug: Starting fetchUsers request..."); // نقطة فحص 4
  console.log(
    "Token being sent to /admin/users:",
    localStorage.getItem("token"),
  );
  try {
    const response = await api.get("/admin/users");
    console.log("Debug: Request successful:", response.data); // نقطة فحص 5
    return response.data;
  } catch (error) {
    console.error("Debug: Request failed with error:", error.response || error); // نقطة فحص 6
    throw error;
  }
});

// في ملف usersSlice.js
export const toggleUserStatus = createAsyncThunk(
  "users/toggleUserStatus",
  async (userId, { rejectWithValue }) => {
    try {
      const response = await api.patch(`/admin/users/${userId}/toggle-status`);
      // نرجع الـ ID مع الـ status الجديدة لنستخدمهم في التحديث
      return { userId, new_status: response.data.new_status };
    } catch (error) {
      return rejectWithValue(error.response.data);
    }
  },
);

const usersSlice = createSlice({
  name: "users",
  initialState: {
    list: [],
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchUsers.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchUsers.fulfilled, (state, action) => {
        state.loading = false;
        state.list = action.payload; // تخزين البيانات القادمة من الـ API
      })
      .addCase(fetchUsers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message;
      })
      // داخل extraReducers في usersSlice.js
      .addCase(toggleUserStatus.fulfilled, (state, action) => {
        // لاحظي هنا استخدمنا state.list
        const user = state.list.find((u) => u.id === action.meta.arg);

        if (user) {
          // تحديث الحالة بناءً على النتيجة القادمة من API
          user.status = action.payload.new_status ? 1 : 0;
        }
      });
  },
});

export default usersSlice.reducer;
