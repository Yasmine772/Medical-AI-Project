const Input = ({ label, type = 'text', id, placeholder, ...props }) => {
  return (
    <div className="flex flex-col gap-1 w-full font-sans">
      {/* اسم الحقل بالرمادي الخفيف مثل الصورة المعمتدة */}
      <label htmlFor={id} className="text-xs font-normal text-gray-500">
        {label}
      </label>
      
      {/* حقل الإدخال مع حواف ناعمة وتأثير عند الضغط عليه (Focus) */}
      <input
        type={type}
        id={id}
        placeholder={placeholder}
        className="w-full px-3 py-2 border border-gray-350 rounded-md outline-none transition-all duration-200 focus:border-[#72A6BB] focus:ring-1 focus:ring-[#72A6BB]/30 text-gray-700 text-sm"
        {...props}
      />
    </div>
  );
};

export default Input;