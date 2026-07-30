

const featuresList = [
  { icon: 'ti-brain', title: 'تشخيص ذكي بالعربية', desc: 'محرك ذكاء اصطناعي يفهم الأعراض بالعربية ويقارنها مع آلاف الأمراض.' },
  { icon: 'ti-user-check', title: 'توثيق طبيب متخصص', desc: 'كل تقرير يمر على طبيب حقيقي مرخّص قبل وصوله إليك.' },
  { icon: 'ti-clock', title: 'نتيجة خلال ساعتين', desc: 'نضمن رد الطبيب خلال ساعتين أو نحوّل الحالة لطبيب آخر تلقائياً.' },
  { icon: 'ti-file-text', title: 'تقرير PDF موثّق', desc: 'وثيقة طبية رسمية بتوقيع الطبيب يمكنك حفظها أو مشاركتها.' },
  { icon: 'ti-shield-check', title: 'خصوصية تامة', desc: 'بياناتك الطبية محمية ولا تُشارك مع أي طرف ثالث.' },
  { icon: 'ti-device-mobile', title: 'من هاتفك مباشرة', desc: 'تطبيق سهل الاستخدام يعمل على iOS و Android.' }
];

export default function FeaturesSection() {
  return (
    <section className="py-16 px-10 max-w-[940px] mx-auto" id="features">
      <div className="text-[12px] font-semibold text-[#72A6BB] tracking-wide mb-2">لماذا DX؟</div>
      <h2 className="text-[28px] font-bold text-black mb-2.5">ما الذي يميزنا</h2>
      <p className="text-[15px] text-gray-600 leading-[1.7] mb-10">نجمع بين دقة الذكاء الاصطناعي ومصداقية الطبيب البشري في منصة واحدة.</p>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
        {featuresList.map((f, index) => (
          <div key={index} className="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <div className="w-[38px] h-[38px] rounded-lg bg-[#72A6BB]/15 flex items-center justify-center mb-3.5 text-[#72A6BB]">
              <i className={`ti ${f.icon} text-[20px]`} aria-hidden="true"></i>
            </div>
            <div className="text-[14px] font-bold text-black mb-1.5">{f.title}</div>
            <div className="text-[12px] text-gray-600 leading-[1.65]">{f.desc}</div>
          </div>
        ))}
      </div>
    </section>
  );
}