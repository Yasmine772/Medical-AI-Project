import { createSlice } from "@reduxjs/toolkit";

const doctorsSlice = createSlice({
  name: "doctors",
  initialState: {
    pending: [
      { id: 101, name: "Dr. Ahmed Ali", specialty: "Neurology" },
      { id: 102, name: "Dr. Sarah Khan", specialty: "Cardiology" },
      { id: 103, name: "Dr. Omar Khaled", specialty: "Pediatrics" },
    ],
    approved: [
      {
        id: 1,
        name: "Dr. Sisu Roy",
        specialty: "Cardiology",
        status: "Active",
      },
      {
        id: 2,
        name: "Dr. Sisu Harnam",
        specialty: "Neurology",
        status: "Suspended",
      },
    ],
  },
  reducers: {
    // الموافقة على طلب (نقل من pending إلى approved)
    approveDoctor: (state, action) => {
      const doctor = state.pending.find((d) => d.id === action.payload);
      if (doctor) {
        state.approved.push({ ...doctor, status: "Active" });
        state.pending = state.pending.filter((d) => d.id !== action.payload);
      }
    },
    // رفض طلب (حذف من pending)
    rejectDoctor: (state, action) => {
      state.pending = state.pending.filter((d) => d.id !== action.payload);
    },
    // حذف طبيب معتمد (حذف من approved)
    deleteDoctor: (state, action) => {
      state.approved = state.approved.filter(
        (doc) => doc.id !== action.payload,
      );
    },
  },
});

// لاحظي أننا قمنا بتحديث الـ export ليحتوي فقط على الدوال المستخدمة
export const { approveDoctor, rejectDoctor, deleteDoctor } =
  doctorsSlice.actions;
export default doctorsSlice.reducer;
