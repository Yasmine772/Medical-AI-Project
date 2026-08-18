import DoctorJoinForm from "./DoctorJoinForm";

import {
  CirclePercent,
  CalendarDays,
  Award,
  MessageSquareHeart,
} from "lucide-react";

const perksList = [
  {
    
    icon: CirclePercent,
    title: "٧٠٪ من كل حالة لك",
    desc: "١٤٬٠٠٠ ل.س لكل مراجعة — تُحوَّل شهرياً بشكل منتظم",
  },
  {
    icon: CalendarDays,
    title: "أنت تحدد جدولك",
    desc: "حدد أيام وساعات توفرك، نحن لا نتدخل",
  },
  {
    icon: Award,
    title: "سمعة طبية رقمية",
    desc: "اسمك وتوقيعك على كل تقرير — يبني حضورك الرقمي",
  },
  {
    icon: MessageSquareHeart,
    title: "تواصل بسيط عبر واتساب",
    desc: "التواصل مع المرضى بدون تعقيد أو أنظمة إضافية",
  },
];

export default function DoctorJoinSection() {
  return (
    <section className="relative bg-gradient-to-b from-white via-[#F8FAFC] to-white border-t border-b border-gray-100 py-20 px-6 sm:px-10 overflow-hidden">
    
      <div className="absolute top-0 right-0 -z-10 w-96 h-96 bg-[#72A6BB]/5 rounded-full blur-3xl pointer-events-none"></div>
      <div className="absolute bottom-0 left-0 -z-10 w-96 h-96 bg-blue-400/5 rounded-full blur-3xl pointer-events-none"></div>

      <div className="max-w-[1000px] mx-auto grid grid-cols-1 md:grid-cols-[1fr_1.4fr] gap-12 lg:gap-16 items-start">
       
        <div className="flex flex-col">
      
          <div className="inline-flex items-center gap-2 self-start px-3 py-1 rounded-full bg-[#72A6BB]/10 text-[#72A6BB] text-[12px] font-bold tracking-wide mb-3 transition-all duration-300 hover:bg-[#72A6BB]/20">
            <span className="w-2 h-2 rounded-full bg-[#72A6BB] animate-pulse"></span>
            للأطباء
          </div>

          <h2 className="text-[26px] sm:text-[28px] font-extrabold text-gray-900 mb-3 tracking-tight">
            انضم إلى شبكة أطباء DX
          </h2>

          <p className="text-[14px] text-gray-600 leading-[1.8] mb-8">
            راجع الحالات من أي مكان وفي أوقاتك المناسبة. أنت تحدد جدولك، نحن
            نرسل لك الحالات.
          </p>

          <div className="flex flex-col gap-4">
            {perksList.map((p, index) => {
              const IconComponent = p.icon; 
              return (
                <div
                  key={index}
                  className="group flex items-start gap-4 p-3 rounded-xl transition-all duration-300 hover:bg-white hover:shadow-md hover:shadow-gray-100 border border-transparent hover:border-gray-100"
                >
                
                  <div className="w-[40px] h-[40px] rounded-xl bg-[#72A6BB]/15 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#72A6BB]">
                    
                    <IconComponent
                      className="w-[20px] h-[20px] text-[#72A6BB] group-hover:text-white transition-colors duration-300"
                      strokeWidth={1.5}
                    />
                  </div>

                  <div>
                    <div className="text-[14px] font-bold text-gray-900 mb-1 transition-colors duration-300 group-hover:text-[#72A6BB]">
                      {p.title}
                    </div>
                    <div className="text-[12px] text-gray-500 leading-[1.6]">
                      {p.desc}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        
        <div className="transition-all duration-500 hover:shadow-xl rounded-2xl bg-white p-6 border border-gray-100">
          <DoctorJoinForm />
        </div>
      </div>
    </section>
  );
}
