import { X, Camera, User } from "lucide-react";
import { useState } from "react";
import { useSelector, useDispatch } from "react-redux";
import { updateProfile } from "../../../store/authSlice";
const ProfileDrawer = ({ isOpen, onClose }) => {
  const { userName, userImage, userEmail } = useSelector((state) => state.auth);

  const dispatch = useDispatch();
  const [nameInput, setNameInput] = useState(userName || "");

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        // هنا نقوم بعمل الـ dispatch للتحديث
        dispatch(
          updateProfile({
            name: userName, // نحتفظ بالاسم كما هو
            image: reader.result, // هذا هو مسار الصورة بصيغة Base64
          }),
        );
      };
      reader.readAsDataURL(file);
    }
  };
  const handleSave = () => {
    dispatch(updateProfile({ name: nameInput, image: userImage }));
    onClose();
  };

  return (
    <>
      {/* Overlay: الخلفية الشفافة */}
      {/* قمنا بإضافة transition للعتامة (opacity) لتظهر وتختفي بسلاسة */}
      {/* وأضفنا pointer-events-none عندما يكون مغلقاً حتى لا يعيق الضغط على الصفحة */}
      <div
        className={`fixed inset-0 bg-black/30 z-40 transition-opacity duration-300 ease-in-out ${
          isOpen ? "opacity-100" : "opacity-0 pointer-events-none"
        }`}
        onClick={onClose}
      />

      {/* Drawer: القائمة الجانبية */}
      {/* قمنا بتغيير مكانه من right-0 إلى right-[-320px] (خارج الشاشة) */}
      {/* واستخدمنا translate-x-0 للفتحه، و translate-x-full لإخلاقه. */}
      {/* مع transition للحركة */}
      <div
        className={`fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 p-6 flex flex-col transform transition-transform duration-300 ease-in-out ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <div className="flex justify-between items-center mb-8">
          <h2 className="text-xl font-bold text-gray-700">My Profile</h2>
          <button
            onClick={onClose}
            className="p-1 hover:bg-gray-100 rounded-full"
          >
            <X size={20} />
          </button>
        </div>

        {/* Profile Image Upload */}
        <div className="flex flex-col items-center mb-6">
          <label className="relative cursor-pointer group">
            <div className="w-32 h-32 rounded-full border-4 border-[#72A6BB] flex items-center justify-center overflow-hidden bg-gray-50 transition-all duration-300 group-hover:border-[#5a8799]">
              <div className="w-32 h-32 rounded-full border-4 border-[#72A6BB] overflow-hidden bg-gray-50 flex items-center justify-center">
                {userImage ? (
                  <img
                    src={userImage}
                    alt="Profile"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <User size={64} className="text-gray-300" /> // ستظهر الأيقونة إذا لم يرفع صورة
                )}
              </div>
            </div>
            <div className="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <Camera className="text-white" size={32} />
            </div>
            <input
              type="file"
              className="hidden"
              accept="image/*"
              onChange={handleImageChange}
            />
          </label>
          <p className="mt-3 text-sm text-gray-600 font-medium">
            Click to change photo
          </p>
        </div>

        {/* Profile Info Fields */}
        <div className="space-y-5 flex-1">
          <div>
            <label className="text-sm text-gray-500 block mb-1">
              Full Name
            </label>
            <input
              type="text"
              value={nameInput}
              onChange={(e) => setNameInput(e.target.value)}
              className="w-full p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#72A6BB] transition"
            />
          </div>
          <div>
            <label className="text-sm text-gray-500 block mb-1">
              Email Address
            </label>
            <input
              type="email"
              value={userEmail || ""}
              readOnly
              className="w-full p-3 border rounded-xl bg-gray-50"
            />
          </div>
        </div>

        {/* Action Button */}
        <button
          onClick={handleSave}
          className="w-full bg-[#72A6BB] text-white py-4 rounded-2xl font-semibold text-lg shadow-md hover:bg-[#5a8799] transition"
        >
          Save Changes
        </button>
      </div>
    </>
  );
};

export default ProfileDrawer;
