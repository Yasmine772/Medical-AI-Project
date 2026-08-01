import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const ResetPassword = () => {
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();

  const { email, otp } = location.state || {};

  const handleResetSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post("/api/v1/auth/reset-password", {
        email,
        otp,
        password,
        password_confirmation: passwordConfirmation,
      });

      toast.success("Password reset successfully, you can now log in", {
        duration: 3000,
        style: {
          background: "#72A6BB",
          color: "#fff",
          borderRadius: "16px",
          padding: "12px 20px",
          fontWeight: "500",
        },
      });
      navigate("/login");
    } catch (error) {
      toast.error(error.response?.data?.message || "Something went wrong", {
        duration: 3000,
        style: {
          borderRadius: "16px",
        },
      });
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
      <p className="text-sm text-gray-500">Enter your new password below.</p>

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
