import Input from "../../../components/UI/Input";
import Button from "../../../components/UI/Button";
import { useNavigate } from "react-router-dom";
const LoginForm = () => {
  const handleSubmit = (e) => {
    e.preventDefault();
    console.log("Form Submitted");
  };
  const navigate = useNavigate();

  const handleLogin = () => {
    // هنا يفترض أن تضعي منطق التأكد من كلمة السر
    // بمجرد نجاح العملية، ننتقل للـ Home
    navigate("/app/dashboard"); // تأكدي أن هذا المسار يتطابق مع مسار الـ Dashboard في App.jsx
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans"
    >
      {/* عنوان الواجهة */}
      <h2 className="text-xl font-bold text-gray-800 tracking-tight mt-2">
        Login to your{" "}
        <span className="text-medical font-medium">diagnostic account</span>
      </h2>

      {/* حقول الإدخال */}
      <div className="flex flex-col gap-3">
        <Input label="Email Address" type="email" id="email" required />

        <Input label="Password" type="password" id="password" required />
      </div>

      {/* رابط forgot password - أخضر غامق وتحته خط */}
      <div className="text-left">
        <a
          href="#forgot"
          className="text-[#58889B] font-normal text-sm underline underline-offset-4 decoration-1 hover:text-gray-950 transition-colors"
        >
          forgot password
        </a>
      </div>

      {/* الأزرار المتطابقة المتتالية عمودياً */}
      <div className="flex flex-col gap-2.5 mt-2">
        <Button
          type="button"
          onClick={() => console.log("Confirming Email...")}
        >
          confirm email
        </Button>

        <Button type="submit" onClick={handleLogin}>
          Login
        </Button>
      </div>
    </form>
  );
};

export default LoginForm;
