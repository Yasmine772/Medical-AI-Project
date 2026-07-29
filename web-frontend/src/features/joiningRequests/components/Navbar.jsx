

export default function Navbar() {
  return (
    <nav className="bg-white border-b border-gray-200 px-10 flex items-center justify-between h-14 sticky top-0 z-50">
      <div className="text-[22px] font-bold text-[#72A6BB] tracking-tight">DX</div>
      <div className="hidden md:flex items-center gap-7">
        <a className="text-[13px] text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors" href="#how">كيف يعمل؟</a>
        <a className="text-[13px] text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors" href="#doctor-join">للأطباء</a>
        <a className="text-[13px] text-gray-600 hover:text-[#72A6BB] cursor-pointer transition-colors" href="#features">عن المنصة</a>
      </div>
      <button 
        onClick={() => alert('سيتم التوجيه لصفحة تسجيل الدخول')}
        className="text-[13px] px-5 py-2 rounded-lg bg-[#72A6BB] text-white font-semibold hover:bg-[#5e8d9f] transition-colors cursor-pointer"
      >
        ابدأ الفحص مجاناً
      </button>
    </nav>
  );
}