

export default function HeroSection() {
  return (
    <section className="py-20 px-10 text-center max-w-[680px] mx-auto">
      <div className="inline-flex items-center gap-1.5 bg-[#72A6BB]/15 text-[#72A6BB] text-xs font-semibold px-3.5 py-1.5 rounded-full mb-5.5">
        <i className="ti ti-sparkles text-[14px]" aria-hidden="true"></i>
        تشخيص ذكي موثّق طبياً
      </div>
      <h1 className="text-[42px] font-bold text-black leading-tight mb-4.5">
        اعرف حالتك<br /><span className="text-[#72A6BB]">قبل ما تروح الطبيب</span>
      </h1>
      <p className="text-[16px] text-gray-600 leading-[1.75] mb-9">
        أدخل أعراضك، الذكاء الاصطناعي يحللها، وطبيب متخصص يراجع النتيجة ويرسل لك تقريراً موثّقاً خلال ساعتين.
      </p>
      <div className="flex gap-3 justify-center flex-wrap">
        <button 
          onClick={() => alert('سيتم التوجيه لصفحة تسجيل الدخول')}
          className="px-7.5 py-3.2 rounded-lg bg-[#72A6BB] text-white font-semibold text-[15px] hover:bg-[#5e8d9f] transition-colors inline-flex items-center gap-2 cursor-pointer"
        >
          <i className="ti ti-stethoscope text-[16px]" aria-hidden="true"></i>
          ابدأ الفحص مجاناً
        </button>
        <button 
          onClick={() => document.getElementById('doctor-join').scrollIntoView({ behavior: 'smooth' })}
          className="px-7.5 py-3.2 rounded-lg bg-white text-black border border-gray-300 font-semibold text-[15px] hover:bg-gray-50 transition-colors inline-flex items-center gap-2 cursor-pointer"
        >
          <i className="ti ti-user-heart text-[16px]" aria-hidden="true"></i>
          انضم كطبيب
        </button>
      </div>
    </section>
  );
}