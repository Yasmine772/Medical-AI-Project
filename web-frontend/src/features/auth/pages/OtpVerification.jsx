import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import api from "../../../api/axios";
import Button from "../../../components/UI/Button";
import Input from "../../../components/UI/Input";
import { useDispatch } from "react-redux";
import { loginSuccess } from "../../../store/authSlice";
const OtpVerification = () => {
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  // استقبال الإيميل الممرر من صفحة اللوجين
  const email = location.state?.email;
  const dispatch = useDispatch();
  const handleVerify = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await api.post("/admin/verifyOtp", { email, otp });

      // استخرجي التوكن من استجابة الـ OTP
      // تأكدي من المسار الصحيح (response.data.data.access_token مثلاً)
      const token = response.data.data?.token;
      if (token) {
        // هنا نقوم بتخزين التوكن في Redux والـ localStorage
        dispatch(loginSuccess({ token, email }));

        // الآن انتقلي للداشبورد
        navigate("/app/dashboard");
      }
    } catch (error) {
      alert("فشل التحقق: " + (error.response?.data?.message || "حدث خطأ"));
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    try {
      await api.post("/admin/resendOtp", { email });
      alert("تم إرسال كود جديد إلى إيميلك");
    } catch (error) {
      console.log(error);
      alert("فشل إرسال الكود");
    }
  };

  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-6">
      <form
        onSubmit={handleVerify}
        className="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm"
      >
        <h2 className="text-xl font-bold mb-4">Verification Required</h2>
        <p className="text-sm text-gray-600 mb-6">
          Enter the OTP sent to {email}
        </p>

        <Input
          label="OTP Code"
          type="text"
          value={otp}
          onChange={(e) => setOtp(e.target.value)}
          required
        />

        <Button type="submit" className="w-full mt-4" disabled={loading}>
          {loading ? "Verifying..." : "Verify"}
        </Button>

        <button
          type="button"
          onClick={handleResend}
          className="text-sm text-[#58889B] underline mt-4 block w-full text-center"
        >
          Resend OTP
        </button>
      </form>
    </div>
  );
};

export default OtpVerification;
