
import DoctorJoinForm from './DoctorJoinForm';

const perksList = [
  { icon: 'ti-cash', title: '٧٠٪ من كل حالة لك', desc: '١٤٬٠٠٠ ل.س لكل مراجعة — تُحوَّل شهرياً بشكل منتظم' },
  { icon: 'ti-calendar', title: 'أنت تحدد جدولك', desc: 'حدد أيام وساعات توفرك، نحن لا نتدخل' },
  { icon: 'ti-star', title: 'سمعة طبية رقمية', desc: 'اسمك وتوقيعك على كل تقرير — يبني حضورك الرقمي' },
  { icon: 'ti-brand-whatsapp', title: 'تواصل بسيط عبر واتساب', desc: 'التواصل مع المرضى بدون تعقيد أو أنظمة إضافية' }
];

export default function DoctorJoinSection() {
  return (
    <div className="bg-white border-t border-b border-gray-200 py-16 px-10">
      <div className="max-w-[940px] mx-auto grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-14 items-start">
        
        <div>
          <div className="text-[12px] font-semibold text-[#72A6BB] tracking-wide mb-2">للأطباء</div>
          <h2 className="text-[24px] font-bold text-black mb-2.5">انضم إلى شبكة أطباء DX</h2>
          <p className="text-[13px] text-gray-600 leading-[1.7] mb-0">راجع الحالات من أي مكان وفي أوقاتك المناسبة. أنت تحدد جدولك، نحن نرسل لك الحالات.</p>
          
          <div className="flex flex-col gap-3.5 mt-7">
            {perksList.map((p, index) => (
              <div key={index} className="flex items-start gap-3">
                <div className="w-[34px] h-[34px] rounded-lg bg-[#72A6BB]/15 flex items-center justify-center shrink-0">
                  <i className={`ti ${p.icon} text-[17px] text-[#72A6BB]`} aria-hidden="true"></i>
                </div>
                <div>
                  <div className="text-[13px] font-bold text-black mb-0.5">{p.title}</div>
                  <div className="text-[12px] text-gray-600 leading-[1.5]">{p.desc}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        <DoctorJoinForm />

      </div>
    </div>
  );
}