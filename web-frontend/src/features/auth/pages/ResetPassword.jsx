import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";

const ResetPassword = () => {
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  
  const navigate = useNavigate();
  const location = useLocation();

  // استقبال الإيميل والـ OTP من الصفحة السابقة
  const { email, otp } = location.state || {};

  const handleResetSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    

    try {
      // إرسال البيانات للـ API
      await api.post("/api/v1/auth/reset-password", {
        email,
        otp,
        password,
        password_confirmation: passwordConfirmation,
      });

      alert("تم تغيير كلمة السر بنجاح! يمكنك الآن تسجيل الدخول.");
      navigate("/login"); // توجيه المستخدم لصفحة تسجيل الدخول
      
    } catch (error) {
      alert("خطأ: " + (error.response?.data?.message || "حدث خطأ أثناء تغيير كلمة السر"));
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={handleResetSubmit}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Reset <span className="text-medical font-medium">password</span>
      </h2>
      <p className="text-sm text-gray-500">
        Enter your new password below.
      </p>

      <Input
        label="New Password"
        type="password"
        id="password"
        required
        value={password}
        onChange={(e) => setPassword(e.target.value)}
      />
      <Input
        label="Confirm Password"
        type="password"
        id="password_confirmation"
        required
        value={passwordConfirmation}
        onChange={(e) => setPasswordConfirmation(e.target.value)}
      />

      <Button type="submit" disabled={loading}>
        {loading ? "Saving..." : "Reset Password"}
      </Button>
    </form>
  );
};

export default ResetPassword;