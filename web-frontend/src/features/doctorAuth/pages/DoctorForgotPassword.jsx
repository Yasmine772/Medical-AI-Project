import { useState } from "react";
import { useNavigate } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const DoctorForgotPassword = () => {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const navigate = useNavigate();

  const handleForgotPassword = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");

    const formData = new FormData();
    formData.append("email", email.trim());

    try {
      const response = await api.post(
        "/doctor/forget-password",
        formData,
        { email: email.trim() },
        {
          headers: { Accept: "application/json" },
        },
      );

      toast.success(
        response.data?.message || "OTP has been sent to your email",
      );

      setTimeout(() => {
        navigate("/verify-reset-otp-doctor", { state: { email } });
      }, 1500);
    } catch (error) {
      toast.error(error.response?.data?.message || "Something went wrong");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex justify-center items-center min-h-screen bg-gray-50 font-sans">
      <form
        onSubmit={handleForgotPassword}
        className="flex flex-col gap-4 w-full max-w-sm bg-white p-8 rounded-3xl shadow-lg border border-gray-100"
      >
        <h2 className="text-2xl font-bold text-gray-800 tracking-tight text-center">
          Forgot <span className="text-[#72A6BB]">Password</span>
        </h2>

        <p className="text-sm text-gray-500 text-center mb-2">
          Enter your email address and we’ll send you an OTP code to reset your
          password.
        </p>

        {message && (
          <div className="p-3 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl text-center">
            {message}
          </div>
        )}

        <div className="flex flex-col gap-3">
          <Input
            label="Email Address"
            type="email"
            id="forgot-email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="Enter your email"
          />
        </div>

        <div className="flex flex-col gap-2.5 mt-2">
          <Button
            type="submit"
            disabled={loading}
            className="bg-[#72A6BB] hover:bg-[#58889B] text-white transition-colors"
          >
            {loading ? "Sending..." : "Send OTP"}
          </Button>
        </div>

        <div className="text-center mt-2">
          <button
            type="button"
            onClick={() => navigate("/doctor-login")}
            className="text-sm text-[#72A6BB] hover:underline"
          >
            Back to Login
          </button>
        </div>
      </form>
    </div>
  );
};

export default DoctorForgotPassword;
