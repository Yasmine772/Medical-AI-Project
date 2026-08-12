import axios from "axios";

const aiApi = axios.create({
  baseURL: "http://127.0.0.1:8000",
//   withCredentials: true,
  headers: {
    Accept: "application/json",
    // ملاحظة: لا تضعي "Content-Type": "application/json" هنا، 
    // لأن إرسال الملفات (FormData) يتطلب أن يحدد المتصفح الـ boundary تلقائياً
  },
});

aiApi.interceptors.request.use((config) => {
  const token =
    localStorage.getItem("doctor_token") || localStorage.getItem("token");

  if (token) {
    const cleanToken = token.replace(/['"]+/g, "").trim();
    config.headers.Authorization = `Bearer ${cleanToken}`;
  }
  return config;
});

export default aiApi;