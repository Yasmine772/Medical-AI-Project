import LoginForm from "../components/LoginForm";
import logoImg from "../../../assets/medical-logo.png";
import medicalImg from "../../../assets/medical-illustration.png";
import { useSearchParams } from "react-router-dom";

const LoginPage = () => {
  const [searchParams] = useSearchParams();
  const role = searchParams.get("role");

  const getWelcomeText = () => {
    return role === "doctor"
      ? "Welcome Back, Doctor"
      : "Welcome Back, Administrator";
  };

  const getSubText = () => {
    return role === "doctor"
      ? "Access your diagnostic dashboard"
      : "Manage your system settings";
  };

  return (
    <div className="min-h-screen w-full bg-[#BACED6] flex items-center justify-center p-6 antialiased font-sans relative overflow-hidden">
      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-30%",
          left: "-20%",
          width: "70vw",
          height: "70vw",
          backgroundColor: "#A3BCC7",
          zIndex: 1,
        }}
      />

      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-15%",
          left: "-10%",
          width: "45vw",
          height: "45vw",
          backgroundColor: "#B1C7D0",
          zIndex: 2,
        }}
      />

      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          top: "-25%",
          left: "-15%",
          width: "55vw",
          height: "55vw",
          backgroundColor: "#C5D6DC",
          zIndex: 1,
        }}
      />

      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-25%",
          right: "-15%",
          width: "55vw",
          height: "55vw",
          backgroundColor: "#CDE1E8",
          zIndex: 1,
        }}
      />

      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-10%",
          right: "-5%",
          width: "35vw",
          height: "35vw",
          backgroundColor: "#DBE9EE",
          zIndex: 2,
        }}
      />

      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "15%",
          right: "8%",
          width: "6vw",
          height: "6vw",
          backgroundColor: "#EDF4F7",
          zIndex: 3,
        }}
      />

      {/* card */}
      <div
        className="bg-white rounded-2xl shadow-2xl shadow-slate-500/20 w-full max-w-4xl min-h-[520px] grid md:grid-cols-2 overflow-hidden border border-white/40 relative"
        style={{ zIndex: 10 }}
      >
        {/* Medical Illustration */}
        <div className="hidden md:flex flex-col justify-center items-center bg-[#F8FAFC]/60 p-8 border-r border-gray-100/50 relative">
          <img
            src={medicalImg}
            alt="Medical Diagnostic Illustration"
            className="w-full max-w-[400px] h-auto object-contain transition-transform duration-500 hover:scale-105"
          />
        </div>

        <div className="flex flex-col justify-center p-8 lg:p-12 relative bg-white">
          <div className="md:absolute md:top-10 md:left-12 flex items-center gap-3 mb-8 md:mb-0 shrink-0">
            <img
              src={logoImg}
              alt="MedDiagnostic Logo"
              className="w-17 h-17 object-contain shrink-0"
            />

            <div className="flex flex-col leading-none">
              <div className="flex font-bold text-lg tracking-tight">
                <span className="text-[#72a6bb]">Med</span>

                <span className="text-gray-800">Diagnostic</span>
              </div>
              <span className="text-[10px] text-gray-400 font-semibold tracking-widest uppercase mt-0.5">
                Med Diagnostic Solutions
              </span>
            </div>
          </div>

          <div className="mb-8">
            <h1 className="text-2xl font-bold text-gray-800">
              {getWelcomeText()}
            </h1>
            <p className="text-gray-500 text-sm mt-1">{getSubText()}</p>
          </div>

          <LoginForm role={role} />
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
