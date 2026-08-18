import { useState } from "react";
import { useNavigate } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const ForgotPassword = () => {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleForgotSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // send email to api
      await api.post("/api/v1/auth/forget-password", { email });
      toast.success("OTP sent successfully to your email");

      navigate("/otp-verification-pass", { state: { email } });
    } catch (error) {
      toast.error(
        error.response?.data?.message ||
          "Something went wrong while sending OTP",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={handleForgotSubmit}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Forgot <span className="text-medical font-medium">password?</span>
      </h2>
      <p className="text-sm text-gray-500">
        Enter your email address and we'll send you an OTP to reset your
        password.
      </p>

      <Input
        label="Email Address"
        type="email"
        id="email"
        required
        value={email}
        onChange={(e) => setEmail(e.target.value)}
      />

      <Button type="submit" disabled={loading}>
        {loading ? "Sending..." : "Send OTP"}
      </Button>

      <button
        type="button"
        onClick={() => navigate("/login")}
        className="text-[#58889B] text-sm hover:underline"
      >
        Back to Login
      </button>
    </form>
  );
};

export default ForgotPassword;
