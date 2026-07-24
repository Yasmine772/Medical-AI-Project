import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useDispatch } from "react-redux"; // <--- هذا كان ناقصاً
import { loginSuccess } from "../../../store/authSlice";

import api from "../../../api/axios";

// import OtpVerification from "../pages/OtpVerification";
const LoginForm = () => {
  // const [isVerifying, setIsVerifying] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const dispatch = useDispatch();
  // داخل LoginForm.jsx

  const handleLoginSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    const formData = new FormData();
    formData.append("email", email.trim());
    formData.append("password", password);

    try {
      const response = await api.post("/admin/login", formData, {
        headers: {
          Accept: "application/json",
        },
      });

      console.log("Login Response Data:", response.data);

      // التعديل هنا: استخدام accessToken كما يظهر في الـ JSON الخاص بكِ
      const token = response.data.data?.accessToken;

      if (token) {
        // إذا وجدنا توكن، نقوم بتخزينه والانتقال للداشبورد فوراً
        dispatch(loginSuccess({ token, email }));
        navigate("/app/dashboard");
      } else {
        // إذا لم يرجع توكن، ننتقل للـ OTP
        navigate("/otp-verification", { state: { email } });
      }
    } catch (error) {
      if (error.response?.status === 403) {
        navigate("/otp-verification", { state: { email } });
      } else {
        alert("خطأ: " + (error.response?.data?.message || "حدث خطأ"));
      }
    } finally {
      setLoading(false);
    }
  };

  // const handleLoginSubmit = async (e) => {
  //   e.preventDefault();
  //   setLoading(true);

  //   const formData = new FormData();
  //   formData.append("email", email);
  //   formData.append("password", password);

  //   try {
  //     // بما أن الـ API لا يرسل التوكن هنا، لا نتوقع وجوده
  //     await api.post("/admin/login", formData);

  //     // إذا نجح الـ Login (بدون خطأ 403)، انتقلي لصفحة الـ OTP
  //     // (أو ربما يرسل السيرفر كود OTP تلقائياً عند نجاح هذا الطلب)
  //     navigate("/otp-verification", { state: { email } });

  //   } catch (error) {
  //     // الحالة 403 هي الحالة التي تعني أن المستخدم يحتاج للتحقق عبر OTP
  //     if (error.response?.status === 403) {
  //       console.log("Redirecting to OTP due to 403...");
  //       navigate("/otp-verification", { state: { email } });
  //     } else {
  //       // أي خطأ آخر (مثل كلمة سر خاطئة)
  //       console.log("Error object:", error);
  //       alert("خطأ: " + (error.response?.data?.message || "حدث خطأ غير متوقع"));
  //     }
  //   } finally {
  //     setLoading(false);
  //   }
  // };

  return (
    <form
      onSubmit={handleLoginSubmit}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Login to your{" "}
        <span className="text-medical font-medium">diagnostic account</span>
      </h2>

      <div className="flex flex-col gap-3">
        <Input
          label="Email Address"
          type="email"
          id="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <Input
          label="Password"
          type="password"
          id="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
      </div>

      <div className="text-left">
        <button
          type="button"
          onClick={() => navigate("/forgot-password")} // تأكدي من تعريف المسار في الـ Router
          className="text-[#58889B] font-normal text-sm underline underline-offset-4 decoration-1 hover:text-gray-950 transition-colors"
        >
          forgot password
        </button>
      </div>

      <div className="flex flex-col gap-2.5 mt-2">
        <Button type="submit" disabled={loading}>
          {loading ? "Logging in..." : "Login"}
        </Button>
      </div>
    </form>
  );
};

export default LoginForm;
