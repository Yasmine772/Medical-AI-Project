import { Check, Send } from "lucide-react";

const ConfirmModal = ({ isOpen, onClose, onConfirm }) => {
  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-[24px] w-full max-w-sm overflow-y-auto shadow-xl text-center p-6"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="w-12 h-12 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-3">
          <Send size={24} />
        </div>
        <h3 className="font-bold text-sm text-[#2c2c2a] mb-1">
          Send Report to Patient?
        </h3>
        <p className="text-xs text-gray-400 leading-relaxed mb-4">
          Once sent, the patient will receive the PDF report verified with your
          name and signature — this action cannot be undone.
        </p>

        <div className="flex gap-2 justify-center">
          <button
            onClick={onClose}
            className="px-4 py-2 text-xs rounded-xl border bg-white hover:bg-gray-100"
          >
            Back
          </button>
          <button
            onClick={onConfirm}
            className="flex items-center gap-1 px-4 py-2 text-xs font-semibold rounded-xl bg-[#72A6BB] text-white hover:bg-[#5f92a6]"
          >
            <Check size={14} /> Yes, Send Report
          </button>
        </div>
      </div>
    </div>
  );
};

export default ConfirmModal;
