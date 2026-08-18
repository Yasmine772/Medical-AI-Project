import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const OtpVerificationPass = () => {
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  // email to gorget password page
  const email = location.state?.email;

  const handleVerifyOtp = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post("/api/v1/auth/verifyOtp", {
        email: email,
        otp: otp,
      });

      toast.success("OTP verified successfully");

      // go to reset password page
      navigate("/reset-password", { state: { email, otp } });
    } catch (error) {
     toast.error(
        error.response?.data?.message || "Invalid or expired OTP code"
      );
    } finally {
      setLoading(false);
    }
  };

  const handleResendOtp = async () => {
    setResending(true);
    try {
      await api.post("/api/v1/auth/resendOtp", { email });
      toast.success("OTP resent to your email");
    } catch (error) {
      toast.error(error.response?.data?.message || "Resend failed");
    } finally {
      setResending(false);
    }
  };
  return (
    <form
      onSubmit={handleVerifyOtp}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Verify <span className="text-medical font-medium">OTP</span>
      </h2>
      <p className="text-sm text-gray-500">
        Enter the 6-digit code sent to <strong>{email}</strong>
      </p>

      <Input
        label="Enter OTP"
        type="text"
        id="otp"
        required
        maxLength="6"
        value={otp}
        onChange={(e) => setOtp(e.target.value.replace(/[^0-9]/g, ""))}
      />

      <Button type="submit" disabled={loading}>
        {loading ? "Verifying..." : "Verify Code"}
      </Button>

      <div className="flex justify-between items-center text-sm mt-1">
        <button
          type="button"
          onClick={() => navigate("/forgot-password")}
          className="text-[#58889B] hover:underline"
        >
          Back
        </button>

        <button
          type="button"
          onClick={handleResendOtp}
          disabled={resending}
          className="text-medical font-medium hover:underline disabled:text-gray-400"
        >
          {resending ? "Sending..." : "Resend OTP"}
        </button>
      </div>
    </form>
  );
};

export default OtpVerificationPass;
