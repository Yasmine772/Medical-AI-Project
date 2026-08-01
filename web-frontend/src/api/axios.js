import axios from "axios";

const api = axios.create({
  baseURL: "http://127.0.0.1:8000",
  withCredentials: true,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

api.interceptors.request.use((config) => {
  if (
    config.url.includes("/admin/login") ||
    config.url.includes("/doctor/login")
  ) {
    return config;
  }

  const token =
    localStorage.getItem("doctor_token") || localStorage.getItem("token");

  if (token) {
    const cleanToken = token.replace(/['"]+/g, "").trim();
    config.headers.Authorization = `Bearer ${cleanToken}`;
  }
  console.log("Final Authorization Header Sent:", config.headers.Authorization);
  return config;
});

export default api;
