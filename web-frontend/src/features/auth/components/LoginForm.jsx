import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useDispatch } from "react-redux";
import api from "../../../api/axios";
import { loginSuccess } from "../../../store/authSlice";
// import OtpVerification from "../pages/OtpVerification";
const LoginForm = ({ role }) => {
  // const [isVerifying, setIsVerifying] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const handleLoginSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    

    try {
      // نرسل الطلب للباك إند
      const response = await api.post("/admin/login", { email, password });
      console.log("الرد من السيرفر:", response.data);
      // إذا نجح الطلب، نخزن التوكن في الـ Redux والـ localStorage
      const token =
        response.data.access_token || response.data.data?.access_token;

      if (token) {
        dispatch(loginSuccess({ token, email }));
        navigate(role === "doctor" ? "/doctor/dashboard" : "/app/dashboard");
      } else {
        alert(
          "لم يتم العثور على التوكن في استجابة السيرفر، تأكدي من الـ Console",
        );
      }
    } catch (error) {
      // هذا السطر سيكشف لنا السبب الحقيقي للخطأ (هل هو خطأ 404، 422، أو 401؟)
      console.log("Full Error Object:", error);
      console.log("Response Data:", error.response?.data);
      alert(
        "خطأ في تسجيل الدخول: " +
          (error.response?.data?.message || "Check Console"),
      );
    } finally {
      setLoading(false);
    }
  };

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
        <a
          href="#forgot"
          className="text-[#58889B] font-normal text-sm underline underline-offset-4 decoration-1 hover:text-gray-950 transition-colors"
        >
          forgot password
        </a>
      </div>

      <div className="flex flex-col gap-2.5 mt-2">
        <Button
          type="button"
          // onClick={() => setIsVerifying(true)} // تفعيل العرض
        >
          confirm email
        </Button>
        <Button type="submit" disabled={loading}>
          {loading ? "Logging in..." : "Login"}
        </Button>
      </div>
    </form>
  );
};

export default LoginForm;
