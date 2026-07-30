

const steps = [
  { num: '١', title: 'أدخل أعراضك', desc: 'أجب على أسئلة بسيطة عن ما تشعر به، واحدة تلو الأخرى.' },
  { num: '٢', title: 'شاهد النتيجة الأولية', desc: 'الذكاء الاصطناعي يرتب الأمراض المحتملة مع نسبة كل منها مجاناً.' },
  { num: '٣', title: 'طبيب يراجع حالتك', desc: 'بعد الدفع، طبيب متخصص يراجع نتيجتك خلال ساعتين.' },
  { num: '٤', title: 'استلم تقريرك', desc: 'تقرير PDF موثّق بتوقيع الطبيب مع نصائح مخصصة لحالتك.' }
];

export default function HowItWorks() {
  return (
    <section className="py-16 px-10 max-w-[940px] mx-auto" id="how">
      <div className="text-[12px] font-semibold text-[#72A6BB] tracking-wide mb-2">كيف يعمل؟</div>
      <h2 className="text-[28px] font-bold text-black mb-2.5">أربع خطوات بسيطة</h2>
      <p className="text-[15px] text-gray-600 leading-[1.7] mb-10">من إدخال الأعراض حتى استلام التقرير — كل شيء خلال ساعتين.</p>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        {steps.map((s, index) => (
          <div key={index} className="bg-white border border-gray-200 rounded-xl p-5.5 shadow-sm">
            <div className="w-[34px] h-[34px] rounded-full bg-[#72A6BB]/15 text-[#72A6BB] text-[14px] font-bold flex items-center justify-center mb-3.5">
              {s.num}
            </div>
            <div className="text-[14px] font-bold text-black mb-2">{s.title}</div>
            <div className="text-[12px] text-gray-600 leading-[1.65]">{s.desc}</div>
          </div>
        ))}
      </div>
    </section>
  );
}