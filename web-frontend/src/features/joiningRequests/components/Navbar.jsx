// استيراد أيقونات Lucide React المناسبة للروابط والإجراءات
import { HelpCircle, Stethoscope, Info, Sparkles } from "lucide-react";

export default function Navbar() {
  return (
    <nav className="bg-white/80 backdrop-blur-md border-b border-gray-100 px-6 sm:px-10 flex items-center justify-between h-16 sticky top-0 z-50 transition-all duration-300 shadow-xs">
      {/* الشعار مع أيقونة صغيرة */}
      <div className="flex items-center gap-2 group cursor-pointer">
        <div className="w-8 h-8 rounded-xl bg-[#72A6BB]/15 flex items-center justify-center text-[#72A6BB] transition-transform duration-300 group-hover:scale-110">
          <Sparkles className="w-4 h-4" />
        </div>
        <span className="text-[22px] font-extrabold text-[#72A6BB] tracking-tight">
          VitaLia
        </span>
      </div>

      {/* الروابط الوسطى مع الأيقونات والحركات */}
      <div className="hidden md:flex items-center gap-8">
        <a
          className="group flex items-center gap-1.5 text-[14px] font-medium text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors duration-300"
          href="#how"
        >
          <HelpCircle className="w-4 h-4 text-gray-400 group-hover:text-[#72A6BB] transition-colors duration-300" />
          كيف يعمل؟
        </a>

        <a
          className="group flex items-center gap-1.5 text-[14px] font-medium text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors duration-300"
          href="#doctor-join"
        >
          <Stethoscope className="w-4 h-4 text-gray-400 group-hover:text-[#72A6BB] transition-colors duration-300" />
          للأطباء
        </a>

        <a
          className="group flex items-center gap-1.5 text-[14px] font-medium text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors duration-300"
          href="#features"
        >
          <Info className="w-4 h-4 text-gray-400 group-hover:text-[#72A6BB] transition-colors duration-300" />
          عن المنصة
        </a>
      </div>

      {/* زر ابدأ الفحص مجاناً */}
      <button
        onClick={() => alert("سيتم التوجيه لصفحة تسجيل الدخول")}
        className="group relative inline-flex items-center gap-2 text-[13px] px-5 py-2.5 rounded-xl bg-[#72A6BB] text-white font-bold hover:bg-[#5e8d9f] transition-all duration-300 hover:shadow-md hover:shadow-[#72A6BB]/20 cursor-pointer active:scale-95"
      >
        <Stethoscope className="w-4 h-4 transition-transform duration-300 group-hover:rotate-12" />
        ابدأ الفحص مجاناً
      </button>
    </nav>
  );
}
