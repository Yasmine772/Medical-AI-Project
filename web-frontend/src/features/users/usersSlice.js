import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";

import api from "../../api/axios";

// 1.Thunk function to fetch users from the backend
export const fetchUsers = createAsyncThunk("users/fetchUsers", async () => {
  console.log("Debug: Starting fetchUsers request...");
  console.log(
    "Token being sent to /admin/users:",
    localStorage.getItem("token"),
  );
  try {
    const response = await api.get("/admin/users");
    console.log("Debug: Request successful:", response.data);
    return response.data;
  } catch (error) {
    console.error("Debug: Request failed with error:", error.response || error);
    throw error;
  }
});

// usersSlice.js
export const toggleUserStatus = createAsyncThunk(
  "users/toggleUserStatus",
  async (userId, { rejectWithValue }) => {
    try {
      const response = await api.patch(`/admin/users/${userId}/toggle-status`);
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
        state.list = action.payload; // store data from API
      })
      .addCase(fetchUsers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message;
      })

      .addCase(toggleUserStatus.fulfilled, (state, action) => {
        const user = state.list.find((u) => u.id === action.meta.arg);

        if (user) {
          user.status = action.payload.new_status ? 1 : 0;
        }
      });
  },
});

export default usersSlice.reducer;
