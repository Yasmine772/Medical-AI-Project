import { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const DoctorVerifyResetOtp = () => {
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const email =
    location.state?.email || localStorage.getItem("pending_doctor_email") || "";

  const handleVerifyOtp = async (e) => {
    e.preventDefault();
    if (!email) {
      toast.error("Email is missing. Please start over.");
      navigate("/doctor-forgot-password");
      return;
    }

    setLoading(true);

    const formData = new FormData();
    formData.append("email", email.trim());
    formData.append("otp", otp.trim());

    try {
      const response = await api.post("/doctor/verifyOtp", formData, {
        headers: { Accept: "application/json" },
      });

      const token = response.data.data?.token || response.data.token;
      toast.success("OTP verified successfully!");

      navigate("/reset-password-doctor", {
        state: { email, token, otp: otp.trim() },
      });
    } catch (error) {
      toast.error(
        "Verification failed: " +
          (error.response?.data?.message || "Invalid OTP code"),
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex justify-center items-center min-h-screen bg-gray-50 font-sans">
      <form
        onSubmit={handleVerifyOtp}
        className="flex flex-col gap-4 w-full max-w-sm bg-white p-8 rounded-3xl shadow-lg border border-gray-100"
      >
        <h2 className="text-2xl font-bold text-gray-800 tracking-tight text-center">
          Verify <span className="text-[#72A6BB]">OTP</span>
        </h2>

        <p className="text-sm text-gray-500 text-center mb-2">
          Please enter the code sent to <br />
          <span className="font-semibold text-gray-700">{email}</span>
        </p>

        <div className="flex flex-col gap-3">
          <Input
            label="OTP Code"
            type="text"
            id="reset-otp"
            required
            maxLength={6}
            value={otp}
            onChange={(e) => setOtp(e.target.value)}
            placeholder="Enter 6-digit OTP"
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
      </form>
    </div>
  );
};

export default DoctorVerifyResetOtp;
