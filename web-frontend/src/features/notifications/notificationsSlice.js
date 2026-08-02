import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import api from "../../api/axios";

export const fetchUnreadCount = createAsyncThunk(
  "notifications/fetchUnreadCount",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get("api/admin/notifications/count-unread");

      return response.data.data.unreadNotificationNumber;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || "Failed to fetch unread count",
      );
    }
  },
);

export const markAllNotificationsAsRead = createAsyncThunk(
  "notifications/markAllAsRead",
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.patch(
        "api/admin/notifications/mark-all-as-read",
      );
      return response.data.message;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message ||
          "Failed to mark all notifications as read",
      );
    }
  },
);

export const markNotificationAsRead = createAsyncThunk(
  "notifications/markAsRead",
  async (notificationId, { rejectWithValue }) => {
    try {
      const response = await api.patch(
        `api/admin/notifications/${notificationId}/read`,
      );
      return { notificationId, message: response.data.message };
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || "Failed to mark as read",
      );
    }
  },
);

export const fetchNotifications = createAsyncThunk(
  "notifications/fetchNotifications",
  async (_, { rejectWithValue }) => {
    console.log(
      "Token right before fetching notifications:",
      localStorage.getItem("token"),
    );
    try {
      const response = await api.get("api/admin/notifications");

      return response.data.data || response.data;
    } catch (error) {
      console.log("Error Response Object:", error.response);
      return rejectWithValue(
        error.response?.data?.message || "Failed to fetch notifications",
      );
    }
  },
  {
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
    unreadCount: 0,
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
      })

      .addCase(markNotificationAsRead.fulfilled, (state, action) => {
        const { notificationId } = action.payload;

        const notification = state.list.find((n) => n.id === notificationId);
        if (notification) {
          notification.read_at = new Date().toISOString();
        }
      })

      .addCase(markAllNotificationsAsRead.fulfilled, (state) => {
        const currentTime = new Date().toISOString();
        state.list.forEach((notification) => {
          if (!notification.read_at) {
            notification.read_at = currentTime;
          }
        });
      })
      .addCase(fetchUnreadCount.fulfilled, (state, action) => {
        state.unreadCount = action.payload;
      });
  },
});

export default notificationsSlice.reducer;
