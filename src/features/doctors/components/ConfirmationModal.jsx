const ConfirmationModal = ({ isOpen, onClose, onConfirm, title, message }) => {
  if (!isOpen) return null;

  return (
    // هذا الـ div هو "الغطاء" الذي يغطي كامل الشاشة
    // z-[9999] يجعله فوق كل شيء
    // bg-black/30 يعطي اللون الغامق الخفيف
    // backdrop-blur-sm هو سر التغبيش الاحترافي
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30 backdrop-blur-sm p-4">
      {/* الكارد (النافذة البيضاء) */}
      <div className="bg-white p-8 rounded-[32px] w-full max-w-sm text-center shadow-2xl border border-white/20 animate-in fade-in zoom-in duration-300">
        <h2 className="text-2xl font-bold text-gray-800 mb-2">{title}</h2>
        <p className="text-gray-500 mb-8">{message}</p>

        <div className="flex gap-3">
          <button
            onClick={onClose}
            className="flex-1 py-3 rounded-2xl border border-gray-200 hover:bg-gray-50 font-medium transition-all"
          >
            Cancel
          </button>
          <button
            onClick={onConfirm}
            className="flex-1 py-3 rounded-2xl bg-[#72A6BB] text-white hover:bg-[#5a93a8] font-medium transition-all"
          >
            Yes, Confirm
          </button>
        </div>
      </div>
    </div>
  );
};

export default ConfirmationModal;
