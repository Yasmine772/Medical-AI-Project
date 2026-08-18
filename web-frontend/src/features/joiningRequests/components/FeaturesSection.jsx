
import {
  Brain,
  UserCheck,
  Clock,
  FileText,
  ShieldCheck,
  Smartphone,
} from "lucide-react";

const featuresList = [
  {
    icon: Brain,
    title: "تشخيص ذكي بالعربية",
    desc: "محرك ذكاء اصطناعي يفهم الأعراض بالعربية ويقارنها مع آلاف الأمراض.",
  },
  {
    icon: UserCheck,
    title: "توثيق طبيب متخصص",
    desc: "كل تقرير يمر على طبيب حقيقي مرخّص قبل وصوله إليك.",
  },
  {
    icon: Clock,
    title: "نتيجة خلال ساعتين",
    desc: "نضمن رد الطبيب خلال ساعتين أو نحوّل الحالة لطبيب آخر تلقائياً.",
  },
  {
    icon: FileText,
    title: "تقرير PDF موثّق",
    desc: "وثيقة طبية رسمية بتوقيع الطبيب يمكنك حفظها أو مشاركتها.",
  },
  {
    icon: ShieldCheck,
    title: "خصوصية تامة",
    desc: "بياناتك الطبية محمية ولا تُشارك مع أي طرف ثالث.",
  },
  {
    icon: Smartphone,
    title: "من هاتفك مباشرة",
    desc: "تطبيق سهل الاستخدام يعمل على iOS و Android.",
  },
];

export default function FeaturesSection() {
  return (
    <section
      className="py-20 px-6 sm:px-10 max-w-[1000px] mx-auto relative overflow-hidden"
      id="features"
    >
      
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 w-[500px] h-[500px] bg-[#72A6BB]/5 rounded-full blur-3xl pointer-events-none"></div>

      <div className="text-center md:text-right mb-12">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#72A6BB]/10 text-[#72A6BB] text-[12px] font-bold tracking-wide mb-3 transition-all duration-300 hover:bg-[#72A6BB]/20">
          <span className="w-2 h-2 rounded-full bg-[#72A6BB] animate-pulse"></span>
          لماذا VitaLia؟
        </div>

        <h2 className="text-[28px] sm:text-[32px] font-extrabold text-gray-900 mb-3 tracking-tight">
          ما الذي يميزنا
        </h2>

        <p className="text-[14px] sm:text-[15px] text-gray-600 leading-[1.8] max-w-xl">
          نجمع بين دقة الذكاء الاصطناعي ومصداقية الطبيب البشري في منصة واحدة.
        </p>
      </div>

      
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
        {featuresList.map((f, index) => {
          const IconComponent = f.icon;
          return (
            <div
              key={index}
              className="group bg-white border border-gray-100 rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 relative flex flex-col justify-between"
            >
              <div>
               
                <div className="w-[44px] h-[44px] rounded-xl bg-[#72A6BB]/15 flex items-center justify-center mb-4 text-[#72A6BB] transition-all duration-300 group-hover:scale-110 group-hover:bg-[#72A6BB] group-hover:text-white">
                  <IconComponent
                    className="w-[22px] h-[22px]"
                    strokeWidth={1.5}
                  />
                </div>

                <div className="text-[15px] font-bold text-gray-900 mb-2 transition-colors duration-300 group-hover:text-[#72A6BB]">
                  {f.title}
                </div>

                <div className="text-[13px] text-gray-500 leading-[1.7]">
                  {f.desc}
                </div>
              </div>

             
              <div className="absolute bottom-0 left-6 right-6 h-[2px] bg-[#72A6BB] scale-x-0 group-hover:scale-x-100 transition-transform duration-300 rounded-full"></div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
