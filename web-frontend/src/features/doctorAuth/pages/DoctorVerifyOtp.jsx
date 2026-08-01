import { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { useDispatch } from "react-redux";
import { loginSuccess } from "../../../store/authSlice";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const DoctorVerifyOtp = () => {
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const dispatch = useDispatch();
  const email = location.state?.email || "";

  const handleVerifySubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    const formData = new FormData();
    formData.append("email", email.trim());
    formData.append("otp", otp.trim());

    try {
      const response = await api.post("/doctor/verifyOtp", formData, {
        headers: { Accept: "application/json" },
      });

      const token = response.data.data?.token;
      const role = response.data.data?.role; 

      if (token) {
        dispatch(loginSuccess({ token, email, role }));
        toast.success("Account verified successfully!");
        navigate("/Layout/dashboard");
      } else {
        toast.error("Verification succeeded, but token is missing.");
      }
    } catch (error) {
      toast.error(
        "Verification failed: " +
          (error.response?.data?.message || "Something went wrong")
      );
    } finally {
      setLoading(false);
    }
  };

  
  const handleResendOtp = async () => {
    if (!email) {
      toast.error("Email is missing. Please try logging in again.");
      return;
    }

    setResending(true);
    const formData = new FormData();
    formData.append("email", email.trim());

    try {
      const response = await api.post("/doctor/resendOtp", formData, {
        headers: { Accept: "application/json" },
      });
      toast.success(
        response.data?.message ||
          "OTP resent successfully. Please check your email."
      );
    } catch (error) {
     toast.error(
        "Resend failed: " +
          (error.response?.data?.message || "Something went wrong")
      );
    } finally {
      setResending(false);
    }
  };

  return (
    <div className="flex justify-center items-center min-h-screen bg-gray-50 font-sans">
      <form
        onSubmit={handleVerifySubmit}
        className="flex flex-col gap-4 w-full max-w-sm bg-white p-8 rounded-3xl shadow-lg border border-gray-100"
      >
        <h2 className="text-2xl font-bold text-gray-800 tracking-tight text-center">
          Email <span className="text-[#72A6BB]">Verification</span>
        </h2>

        <p className="text-sm text-gray-500 text-center mb-2">
          Please enter the 6-digit OTP code sent to <br />
          <span className="font-semibold text-gray-700">
            {email || "your email"}
          </span>
        </p>

        <div className="flex flex-col gap-3">
          <Input
            label="OTP Code"
            type="text"
            id="doctor-otp"
            required
            maxLength={6}
            value={otp}
            onChange={(e) => setOtp(e.target.value)}
            placeholder="Enter OTP"
          />
        </div>

        <div className="flex flex-col gap-2.5 mt-2">
          <Button
            type="submit"
            disabled={loading}
            className="bg-[#72A6BB] hover:bg-[#58889B] text-white transition-colors"
          >
            {loading ? "Verifying..." : "Verify OTP"}
          </Button>
        </div>

        <div className="text-center mt-2">
          <span className="text-sm text-gray-500">
            Didn't receive the code?{" "}
          </span>
          <button
            type="button"
            onClick={handleResendOtp}
            disabled={resending}
            className="text-[#72A6BB] font-medium text-sm hover:underline disabled:opacity-50"
          >
            {resending ? "Sending..." : "Resend OTP"}
          </button>
        </div>
      </form>
    </div>
  );
};

export default DoctorVerifyOtp;
