import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useDispatch } from "react-redux";
import { loginSuccess } from "../../../store/authSlice";

import api from "../../../api/axios";

const LoginForm = () => {
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
      const response = await api.post("/admin/login", formData, {
        headers: {
          Accept: "application/json",
        },
      });

      console.log("Login Response Data:", response.data);

      const token = response.data.data?.accessToken;

      if (token) {
        // when we found token go to dashboard
        dispatch(loginSuccess({ token, email }));
        navigate("/app/dashboard");
      } else {
        // go to otp page
        navigate("/otp-verification", { state: { email } });
      }
    } catch (error) {
      if (error.response?.status === 403) {
        navigate("/otp-verification", { state: { email } });
      } else {
        alert(
          "wrong : " +
            (error.response?.data?.message || "something went wrong"),
        );
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

export default LoginForm;
