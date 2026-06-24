import LoginForm from "../components/LoginForm";
import logoImg from "../../../assets/medical-logo.png";
import medicalImg from "../../../assets/medical-illustration.png";

const LoginPage = () => {
  return (
    // الخلفية الأساسية الممتدة بلون أزرق طبي ناعم ومطابق
    <div className="min-h-screen w-full bg-[#BACED6] flex items-center justify-center p-6 antialiased font-sans relative overflow-hidden">
      {/* ─── طبقة الدوائر الحادة والمتداخلة تماماً كالصورة الأصلية ─── */}

      {/* القوس الضخم أسفل اليسار */}
      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-30%",
          left: "-20%",
          width: "70vw",
          height: "70vw",
          backgroundColor: "#A3BCC7", // درجة أغمق متداخلة
          zIndex: 1,
        }}
      />

      {/* الدائرة المتموجة المتوسطة في أسفل اليسار */}
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

      {/* الدائرة الكبيرة في أعلى اليسار */}
      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          top: "-25%",
          left: "-15%",
          width: "55vw",
          height: "55vw",
          backgroundColor: "#C5D6DC", // درجة أفتح
          zIndex: 1,
        }}
      />

      {/* القوس الكبير والواضح جداً أسفل اليمين */}
      <div
        className="absolute rounded-full pointer-events-none"
        style={{
          bottom: "-25%",
          right: "-15%",
          width: "55vw",
          height: "55vw",
          backgroundColor: "#CDE1E8", // طبقة ناعمة ومضيئة
          zIndex: 1,
        }}
      />

      {/* الدائرة المتداخلة الثانية أسفل اليمين */}
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

      {/* نجمة الإضاءة أو الدائرة الصغيرة المضيئة أسفل اليمين */}
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

      {/* ─── الكارد البيضاء المركزية ─── */}
      <div
        className="bg-white rounded-2xl shadow-2xl shadow-slate-500/20 w-full max-w-4xl min-h-[520px] grid md:grid-cols-2 overflow-hidden border border-white/40 relative"
        style={{ zIndex: 10 }}
      >
        {/* القسم الأيسر: الـ Medical Illustration */}
        <div className="hidden md:flex flex-col justify-center items-center bg-[#F8FAFC]/60 p-8 border-r border-gray-100/50 relative">
          {/* استبدلنا النص بهذه الصورة */}
          <img
            src={medicalImg}
            alt="Medical Diagnostic Illustration"
            className="w-full max-w-[400px] h-auto object-contain transition-transform duration-500 hover:scale-105"
          />
        </div>

        {/* القسم الأيمن: اللوغو الجديد والفورم */}
        <div className="flex flex-col justify-center p-8 lg:p-12 relative bg-white">
          {/* هيدر اللوغو المحدث باستخدام الصورة الجديدة */}
          <div className="md:absolute md:top-10 md:left-12 flex items-center gap-3 mb-8 md:mb-0 shrink-0">
            <img
              src={logoImg}
              alt="MedDiagnostic Logo"
              className="w-17 h-17 object-contain shrink-0" // ضبط الأبعاد لتكون متناسقة وأنيقة
            />

            <div className="flex flex-col leading-none">
              <div className="flex font-bold text-lg tracking-tight">
                {/* الجزء الأول: أول 3 حروف باللون الفاتح */}
                <span className="text-[#72a6bb]">Med</span>
                {/* الجزء الثاني: باقي الحروف باللون الغامق */}
                <span className="text-gray-800">Diagnostic</span>
              </div>
              <span className="text-[10px] text-gray-400 font-semibold tracking-widest uppercase mt-0.5">
                Med Diagnostic Solutions
              </span>
            </div>
          </div>

          {/* استدعاء مكون الفورم المنظم */}
          <LoginForm />
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
