import { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import api from "../../../api/axios";
import toast from "react-hot-toast";

const DoctorResetPassword = () => {
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const location = useLocation();
  const email = location.state?.email || "";

  const handleResetPassword = async (e) => {
    e.preventDefault();

    if (password !== passwordConfirmation) {
        toast.error("Passwords do not match!");
     
      return;
    }

    setLoading(true);

    try {
      await api.post(
        "/auth/reset-password",
        {
          email: email.trim(),
          password: password,
          password_confirmation: passwordConfirmation,
        },
        {
          headers: { "Content-Type": "application/json", Accept: "application/json" },
        }
      );

      toast.success("Password has been reset successfully! Please login with your new password.");
      
      navigate("/loginDoctor");

    } catch (error) {
     toast.error(
        "Reset failed: " +
          (error.response?.data?.message || "Something went wrong")
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex justify-center items-center min-h-screen bg-gray-50 font-sans">
      <form
        onSubmit={handleResetPassword}
        className="flex flex-col gap-4 w-full max-w-sm bg-white p-8 rounded-3xl shadow-lg border border-gray-100"
      >
        <h2 className="text-2xl font-bold text-gray-800 tracking-tight text-center">
          Reset <span className="text-[#72A6BB]">Password</span>
        </h2>
        
        <p className="text-sm text-gray-500 text-center mb-2">
          Create a new secure password for your account.
        </p>

        <div className="flex flex-col gap-3">
          <Input
            label="New Password"
            type="password"
            id="new-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Enter new password"
          />
          <Input
            label="Confirm Password"
            type="password"
            id="confirm-password"
            required
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            placeholder="Confirm new password"
          />
        </div>

        <div className="flex flex-col gap-2.5 mt-2">
          <Button 
            type="submit" 
            disabled={loading}
            className="bg-[#72A6BB] hover:bg-[#58889B] text-white transition-colors"
          >
            {loading ? "Resetting..." : "Reset Password"}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default DoctorResetPassword;