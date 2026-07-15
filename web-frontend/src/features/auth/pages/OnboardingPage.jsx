import { useNavigate } from "react-router-dom";
import doctorIllustration from "../../../assets/doctor.png"; 
import adminIllustration from "../../../assets/system-admin.png";
import logoImg from "../../../assets/medical-logo.png";
const OnboardingPage = () => {
  const navigate = useNavigate();

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

      
      <div className="relative z-10 flex flex-col items-center text-center mb-16">
        <div className="flex items-center gap-4 mb-6">
          <img
            src={logoImg}
            alt="MedDiagnostic Logo"
            className="w-16 h-16 object-contain rounded-full border-2 border-white shadow-sm"
          />
          <div className="flex flex-col text-left">
            <div className="flex font-bold text-2xl tracking-tight">
              <span className="text-[#58889B]">Vita</span>
              <span className="text-gray-800">Lia</span>
            </div>
            <span className="text-[10px] text-gray-400 font-semibold tracking-widest uppercase">
              Med Diagnostic Solutions
            </span>
          </div>
        </div>
        <h1 className="text-4xl font-bold text-gray-800 mb-2">
          Welcome to MedDiagnostic
        </h1>
        <p className="text-gray-500 text-sm mb-12">
          {" "}
          
          Your AI-Powered Medical Assistant.
        </p>

        <div className="grid md:grid-cols-2 gap-8 w-full max-w-4xl z-10 relative mt-8">
          {/* doctor card*/}
          <div className="bg-white p-8 rounded-3xl shadow-lg flex flex-col items-center text-center transition-all hover:shadow-xl">
            <div className="h-40 w-40 mb-6 flex items-center justify-center">
              <img
                src={doctorIllustration}
                alt="Doctor"
                className="object-contain"
              />
            </div>
            <h2 className="text-2xl font-bold text-gray-800 mb-2">
              For Doctors
            </h2>
            <p className="text-gray-500 mb-8 text-sm">
              AI Diagnostics & Patient Management
            </p>
            <button
              onClick={() => navigate("/login?role=doctor")}
              className="bg-[#58889B] text-white px-10 py-3 rounded-full hover:bg-[#467080] transition-all w-48"
            >
              I am a doctor
            </button>
          </div>

          {/* admin card*/}
          <div className="bg-white p-8 rounded-3xl shadow-lg flex flex-col items-center text-center transition-all hover:shadow-xl">
            <div className="h-40 w-40 mb-6 flex items-center justify-center">
              <img
                src={adminIllustration}
                alt="Admin"
                className="object-contain"
              />
            </div>
            <h2 className="text-2xl font-bold text-gray-800 mb-2">
              For Admins
            </h2>
            <p className="text-gray-500 mb-8 text-sm">
              Platform Management & Data Control
            </p>
            <button
              onClick={() => navigate("/login?role=admin")}
              className="bg-[#58889B] text-white px-10 py-3 rounded-full hover:bg-[#467080] transition-all w-48"
            >
              I am an admin
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OnboardingPage;
