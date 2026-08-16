// استيراد أيقونات Lucide React المناسبة لكل خطوة
import {
  MessageSquareText,
  Activity,
  UserCheck,
  FileCheck,
} from "lucide-react";

const steps = [
  {
    icon: MessageSquareText,
    num: "١",
    title: "أدخل أعراضك",
    desc: "أجب على أسئلة بسيطة عن ما تشعر به، واحدة تلو الأخرى.",
  },
  {
    icon: Activity,
    num: "٢",
    title: "شاهد النتيجة الأولية",
    desc: "الذكاء الاصطناعي يرتب الأمراض المحتملة مع نسبة كل منها مجاناً.",
  },
  {
    icon: UserCheck,
    num: "٣",
    title: "طبيب يراجع حالتك",
    desc: "بعد الدفع، طبيب متخصص يراجع نتيجتك خلال ساعتين.",
  },
  {
    icon: FileCheck,
    num: "٤",
    title: "استلم تقريرك",
    desc: "تقرير PDF موثّق بتوقيع الطبيب مع نصائح مخصصة لحالتك.",
  },
];

export default function HowItWorks() {
  return (
    <section
      className="py-20 px-6 sm:px-10 max-w-[1000px] mx-auto relative overflow-hidden"
      id="how"
    >
      {/* خلفية جمالية هادئة */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 w-[500px] h-[500px] bg-[#72A6BB]/5 rounded-full blur-3xl pointer-events-none"></div>

      {/* الترويسة والعنوان */}
      <div className="text-center md:text-right mb-12">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#72A6BB]/10 text-[#72A6BB] text-[12px] font-bold tracking-wide mb-3 transition-all duration-300 hover:bg-[#72A6BB]/20">
          <span className="w-2 h-2 rounded-full bg-[#72A6BB] animate-pulse"></span>
          كيف يعمل؟
        </div>

        <h2 className="text-[28px] sm:text-[32px] font-extrabold text-gray-900 mb-3 tracking-tight">
          أربع خطوات بسيطة
        </h2>

        <p className="text-[14px] sm:text-[15px] text-gray-600 leading-[1.8] max-w-xl">
          من إدخال الأعراض حتى استلام التقرير — كل شيء خلال ساعتين.
        </p>
      </div>

      {/* شبكة الخطوات */}
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
        {steps.map((s, index) => {
          const IconComponent = s.icon;
          return (
            <div
              key={index}
              className="group bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 relative flex flex-col justify-between"
            >
              <div>
                {/* رأس البطاقة: رقم الخطوة والأيقونة */}
                <div className="flex items-center justify-between mb-4">
                  <div className="w-[44px] h-[44px] rounded-xl bg-[#72A6BB]/15 text-[#72A6BB] flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:bg-[#72A6BB] group-hover:text-white">
                    <IconComponent
                      className="w-[22px] h-[22px]"
                      strokeWidth={1.5}
                    />
                  </div>
                  <span className="text-[20px] font-extrabold text-gray-200 group-hover:text-[#72A6BB]/40 transition-colors duration-300">
                    {s.num}
                  </span>
                </div>

                <div className="text-[15px] font-bold text-gray-900 mb-2 transition-colors duration-300 group-hover:text-[#72A6BB]">
                  {s.title}
                </div>

                <div className="text-[13px] text-gray-500 leading-[1.7]">
                  {s.desc}
                </div>
              </div>

              {/* شريط جمالي سفلي يظهر عند التمرير */}
              <div className="absolute bottom-0 left-6 right-6 h-[2px] bg-[#72A6BB] scale-x-0 group-hover:scale-x-100 transition-transform duration-300 rounded-full"></div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
