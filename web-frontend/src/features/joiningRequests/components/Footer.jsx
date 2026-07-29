

export default function Footer() {
  return (
    <footer className="py-7 px-10 bg-white border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-right">
      <div className="text-[20px] font-bold text-[#72A6BB]">DX</div>
      <div className="flex gap-5">
        <a className="text-[12px] text-gray-500 hover:text-[#72A6BB] transition-colors" href="#">شروط الخدمة</a>
        <a className="text-[12px] text-gray-500 hover:text-[#72A6BB] transition-colors" href="#">سياسة الخصوصية</a>
        <a className="text-[12px] text-gray-500 hover:text-[#72A6BB] transition-colors" href="#">تواصل معنا</a>
      </div>
      <div className="text-[12px] text-gray-500">© 2026 DX — جميع الحقوق محفوظة</div>
    </footer>
  );
}