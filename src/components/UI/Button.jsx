const Button = ({ children, type = 'button', ...props }) => {
  return (
    <button
      type={type}
      className="w-full bg-[#72A6BB] hover:bg-[#58889B] text-white font-normal py-2 px-4 rounded-md transition-all duration-200 text-sm tracking-wide shadow-sm shadow-slate-400/40 active:scale-[0.99] cursor-pointer"
      {...props}
    >
      {children} {/* هنا قمنا باستخدام الـ children لعرض النص المرسل للزر */}
    </button>
  );
};

export default Button;