import { useState } from "react";

const LogoutModal = ({ isOpen, onClose, onConfirm }) => {
  const [loading, setLoading] = useState(false);

  const handleConfirm = () => {
    setLoading(true);
    setTimeout(() => {
      onConfirm();
      setLoading(false); 
    }, 2000);
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
     {/* card */}
      <div className="bg-white p-8 rounded-[32px] w-full max-w-sm text-center shadow-2xl border border-white/20">
        {loading ? (
          <div className="flex flex-col items-center gap-4">
            <div className="w-12 h-12 border-4 border-[#72A6BB] border-t-transparent rounded-full animate-spin"></div>
            <p className="text-lg font-semibold text-gray-700">
              Logging out...
            </p>
          </div>
        ) : (
          <>
            <h2 className="text-2xl font-bold text-gray-800 mb-2">
              Confirm Logout
            </h2>
            <p className="text-gray-500 mb-8">
              Are you sure you want to sign out of your account?
            </p>

            <div className="flex gap-3">
              <button
                onClick={onClose}
                className="flex-1 py-3 rounded-2xl border border-gray-200 hover:bg-gray-50 font-medium transition-all"
              >
                Cancel
              </button>
              <button
                onClick={handleConfirm}
                className="flex-1 py-3 rounded-2xl bg-[#72A6BB] text-white hover:bg-[#5a93a8] font-medium transition-all"
              >
                Yes, Logout
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default LogoutModal;
