import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useDispatch } from "react-redux";
import { loginSuccess } from "../../../store/authSlice";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const DoctorLoginForm = () => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const dispatch = useDispatch();

  const handleLoginSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    const formData = new FormData();
    formData.append("email", email.trim());
    formData.append("password", password);

    try {
      const response = await api.post("/doctor/login", formData, {
        headers: { Accept: "application/json" },
      });

      const token = response.data.data?.access_token;
      const role = response.data.data?.role;

      if (token) {
        dispatch(loginSuccess({ token, email, role }));
        toast.success("Doctor login successfully");
        navigate("/Layout/dashboard");
      } else {
        localStorage.setItem("pending_doctor_email", email.trim());
        toast.success("Please verify your account via OTP");
        navigate("/otp-verification-doctor", {
          state: { email, role: "doctor" },
        });
      }
    } catch (error) {
      if (error.response?.status === 403) {
        localStorage.setItem("pending_doctor_email", email.trim());
        toast.error("Account verification required");
        navigate("/otp-verification-doctor", {
          state: { email, role: "doctor" },
        });
      } else {
        toast.error(error.response?.data?.message || "Something went wrong");
      }
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
          onClick={() => navigate("/forgot-password-doctor")}
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
