import { useState, useRef } from "react";
const ProfilePanel = ({ isOpen, onClose }) => {
  const [profileImage, setProfileImage] = useState(() => {
    return localStorage.getItem("doctorProfileImg") || null;
  });
  const fileInputRef = useRef(null);

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        const base64String = reader.result;
        setProfileImage(base64String);
        localStorage.setItem("doctorProfileImg", base64String);
      };
      reader.readAsDataURL(file);
    }
  };

  // عند فتح المكون (استرجاع الصورة)

  return (
    <>
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 transition-opacity"
          onClick={onClose}
        ></div>
      )}

      <div
        className={`fixed top-0 right-0 h-full w-1/2 bg-white/80 backdrop-blur-2xl shadow-2xl z-50 transform transition-transform duration-500 ease-in-out border-l border-white/50 ${isOpen ? "translate-x-0" : "translate-x-full"}`}
      >
        <div className="p-10">
          <button
            onClick={onClose}
            className="mb-8 text-gray-400 hover:text-gray-800 transition-colors font-bold"
          >
            ✕ Close
          </button>

          <h2 className="text-3xl font-bold text-gray-800 mb-8">
            Doctor Profile
          </h2>

          <div className="space-y-6">
            <div className="flex flex-col items-center mb-8">
              {/* profile photo */}
              <div
                className="w-32 h-32 rounded-full bg-gray-200 border-4 border-white shadow-lg cursor-pointer overflow-hidden relative group"
                onClick={() => fileInputRef.current.click()} // الضغط على الصورة يفتح اختيار الملف
              >
                {profileImage ? (
                  <img
                    src={profileImage}
                    alt="Profile"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="flex items-center justify-center h-full text-gray-400 text-xs">
                    Upload
                  </div>
                )}
                <div className="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs">
                  Edit
                </div>
              </div>

              <input
                type="file"
                ref={fileInputRef}
                onChange={handleImageChange}
                className="hidden"
                accept="image/*"
              />
            </div>
            <div className="p-6 bg-white/50 rounded-3xl border border-white/50">
              <label className="text-xs text-[#72A6BB] font-bold uppercase">
                Full Name
              </label>
              <p className="text-lg font-semibold text-gray-800">Dr. Ahmed</p>
            </div>
            <div className="p-6 bg-white/50 rounded-3xl border border-white/50">
              <label className="text-xs text-[#72A6BB] font-bold uppercase">
                Specialty
              </label>
              <p className="text-lg font-semibold text-gray-800">Dermatology</p>
            </div>
            <div className="p-6 bg-white/50 rounded-3xl border border-white/50">
              <label className="text-xs text-[#72A6BB] font-bold uppercase">
                Contact Email
              </label>
              <p className="text-lg font-semibold text-gray-800">
                dr.ahmed@dx-clinic.com
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default ProfilePanel;
