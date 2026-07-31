import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { loginDoctor } from "../doctorAuthSlice";

const DoctorLoginForm = () => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { loading } = useSelector((state) => state.doctorAuth);

  const handleLoginSubmit = async (e) => {
    e.preventDefault();

    const resultAction = await dispatch(loginDoctor({ email, password }));

    if (loginDoctor.fulfilled.match(resultAction)) {
      const token = resultAction.payload.data?.access_token;
      if (token) {
        navigate("/Layout/dashboard");
      } else {
        // إذا تطلب الأمر تفعيل OTP ولم يكن التوكن موجوداً مباشرة
        navigate("/otp-verification", { state: { email, role: "doctor" } });
      }
    } else if (loginDoctor.rejected.match(resultAction)) {
      const errorPayload = resultAction.payload;
      
      // التعامل مع حالة 403 (التحقق من الإيميل لأول مرة)
      if (errorPayload?.status === 403) {
        navigate("/otp-verification", { state: { email, role: "doctor" } });
      } else {
        alert(
          "wrong : " +
            (errorPayload?.data?.message || "something went wrong")
        );
      }
    }
  };

  return (
    <form
      onSubmit={handleLoginSubmit}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Login to your{" "}
        <span className="text-[#72A6BB] font-medium">doctor account</span>
      </h2>

      <div className="flex flex-col gap-3">
        <Input
          label="Email Address"
          type="email"
          id="doctor-email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <Input
          label="Password"
          type="password"
          id="doctor-password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
      </div>

      <div className="text-left">
        <button
          type="button"
          onClick={() => navigate("/forgot-password")}
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

export default DoctorLoginForm;