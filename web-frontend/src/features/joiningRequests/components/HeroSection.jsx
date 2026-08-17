// استيراد أيقونات Lucide React المناسبة
import { Sparkles, Stethoscope, UserCheck } from "lucide-react";

export default function HeroSection() {
  return (
    <section className="relative py-24 px-6 sm:px-10 text-center max-w-[800px] mx-auto overflow-hidden">
      {/* خلفية جمالية متدرجة وناعمة لإعطاء عمق للتصميم */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 w-[600px] h-[600px] bg-gradient-to-tr from-[#72A6BB]/10 via-blue-200/20 to-transparent rounded-full blur-3xl pointer-events-none"></div>

      {/* شارة الترويسة المتحركة */}
      <div className="inline-flex items-center gap-2 bg-[#72A6BB]/15 text-[#72A6BB] text-[13px] font-bold px-4 py-1.5 rounded-full mb-6 transition-all duration-300 hover:bg-[#72A6BB]/25 hover:scale-105 shadow-sm">
        <Sparkles
          className="w-4 h-4 animate-spin"
          style={{ animationDuration: "6s" }}
        />
        <span>تشخيص ذكي موثّق طبياً</span>
      </div>

      {/* العنوان الرئيسي */}
      <h1 className="text-[38px] sm:text-[48px] font-extrabold text-gray-900 leading-[1.2] mb-6 tracking-tight">
        اعرف حالتك
        <br />
        <span className="text-[#72A6BB] relative inline-block mt-1">
          قبل ما تروح الطبيب
          {/* خط جمالي سفلي تحت النص الملون */}
          <span className="absolute bottom-0 left-0 w-full h-[3px] bg-[#72A6BB]/30 rounded-full"></span>
        </span>
      </h1>

      {/* الفقرة التوضيحية */}
      <p className="text-[15px] sm:text-[16px] text-gray-600 leading-[1.8] max-w-[650px] mx-auto mb-10">
        أدخل أعراضك، الذكاء الاصطناعي يحللها، وطبيب متخصص يراجع النتيجة ويرسل لك
        تقريراً موثّقاً خلال ساعتين.
      </p>

      {/* أزرار الإجراءات */}
      <div className="flex gap-4 justify-center flex-wrap">
        <button
          onClick={() => alert("سيتم التوجيه لصفحة تسجيل الدخول")}
          className="group px-8 py-3.5 rounded-xl bg-[#72A6BB] text-white font-bold text-[15px] hover:bg-[#5e8d9f] transition-all duration-300 hover:shadow-lg hover:shadow-[#72A6BB]/25 inline-flex items-center gap-2.5 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
        >
          <Stethoscope className="w-[18px] h-[18px] transition-transform duration-300 group-hover:scale-110" />
          ابدأ الفحص مجاناً
        </button>

        <button
          onClick={() =>
            document
              .getElementById("doctor-join")
              ?.scrollIntoView({ behavior: "smooth" })
          }
          className="group px-8 py-3.5 rounded-xl bg-white text-gray-800 border-2 border-gray-200 font-bold text-[15px] hover:border-[#72A6BB] hover:text-[#72A6BB] transition-all duration-300 hover:shadow-md inline-flex items-center gap-2.5 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
        >
          <UserCheck className="w-[18px] h-[18px] text-gray-500 group-hover:text-[#72A6BB] transition-colors duration-300" />
          انضم كطبيب
        </button>
      </div>
    </section>
  );
}
