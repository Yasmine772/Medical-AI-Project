import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";

const LoginForm = ({ role }) => {
  const navigate = useNavigate();
  const handleLoginSubmit = (e) => {
    e.preventDefault();

    if (role === "doctor") {
      navigate("/doctor/dashboard");
    } else {
      navigate("/app/dashboard");
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
        <Input label="Email Address" type="email" id="email" required />
        <Input label="Password" type="password" id="password" required />
      </div>

      <div className="text-left">
        <a
          href="#forgot"
          className="text-[#58889B] font-normal text-sm underline underline-offset-4 decoration-1 hover:text-gray-950 transition-colors"
        >
          forgot password
        </a>
      </div>

      <div className="flex flex-col gap-2.5 mt-2">
        <Button
          type="button"
          onClick={() => console.log("Confirming Email...")}
        >
          confirm email
        </Button>
        <Button type="submit">Login</Button>
      </div>
    </form>
  );
};

export default LoginForm;
