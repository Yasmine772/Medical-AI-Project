import { X, Camera, User } from "lucide-react";
import { useState } from "react";
import { useSelector, useDispatch } from "react-redux";
import { updateProfile } from "../../../store/authSlice";
import api from "../../../api/axios";

const ProfileDrawer = ({ isOpen, onClose }) => {
  const { userName, userImage, userEmail, userRole, birthDate, age, gender } =
    useSelector((state) => state.auth);

  const dispatch = useDispatch();

  // 1. تعريف الـ States
  const [nameInput, setNameInput] = useState(userName || "");
  const [birthDateInput, setBirthDateInput] = useState(
    birthDate ? birthDate.split("T")[0] : "",
  );
  const [genderInput, setGenderInput] = useState(gender || "");
  const [ageInput, setAgeInput] = useState(age || "");
  const [avatarFile, setAvatarFile] = useState(null);

  // لحل مشكلة تحديث الحقول عند جلب البيانات من الـ API
  const [prevUserName, setPrevUserName] = useState(userName);
  if (userName !== prevUserName) {
    setPrevUserName(userName);
    setNameInput(userName || "");
    setBirthDateInput(birthDate ? birthDate.split("T")[0] : "");
    setGenderInput(gender || "");
    setAgeInput(age || "");
  }

  // دالة مساعدة لتصحيح مسار الصورة القادم من الباك اند
  const getImageUrl = (avatarPath) => {
    if (!avatarPath) return null;
    if (avatarPath.startsWith("http")) return avatarPath;
    return `http://127.0.0.1:8000/storage/${avatarPath}`;
  };

  // const fetchProfile = useCallback(async () => {
  //   try {
  //     const response = await api.get("/api/v1/auth/profile");
  //     const data = response.data.data;

  //     dispatch(
  //       updateProfile({
  //         name: data.full_name,
  //         image: getImageUrl(data.avatar),
  //         role: data.role ? data.role.join(", ") : "",
  //         birthDate: data.birth_date,
  //         gender: data.gender,
  //         age: data.age,
  //       }),
  //     );
  //   } catch (error) {
  //     console.error("Error fetching profile:", error);
  //   }
  // }, [dispatch]);

  // useEffect(() => {
  //   if (isOpen) {
  //     fetchProfile();
  //   }
  // }, [isOpen, fetchProfile]);

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setAvatarFile(file); // نحفظ الملف الحقيقي محلياً للإرسال

      const reader = new FileReader();
      reader.onloadend = () => {
        dispatch(
          updateProfile({
            name: userName,
            image: reader.result, // رابط معاينة مؤقت للعرض الفوري
          }),
        );
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSave = async () => {
    try {
      const formData = new FormData();

      if (nameInput) formData.append("full_name", nameInput);
      if (birthDateInput) formData.append("birth_date", birthDateInput);
      if (genderInput) formData.append("gender", genderInput);
      if (ageInput) formData.append("age", ageInput);

      if (avatarFile) {
        formData.append("avatar", avatarFile);
      }

      const response = await api.patch("/api/v1/auth/profile", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      const newAvatarPath = response.data?.data?.avatar;
      const finalImage = newAvatarPath ? getImageUrl(newAvatarPath) : userImage;

      dispatch(
        updateProfile({
          name: nameInput,
          birthDate: birthDateInput,
          gender: genderInput,
          age: ageInput,
          image: finalImage, // تم تصحيح اسم المتغير هنا ليتطابق مع المعرّف بالأعلى
        }),
      );

      onClose();
    } catch (error) {
      console.error("Error updating profile:", error.response?.data);
      alert(
        JSON.stringify(
          error.response?.data?.errors || "Error updating profile",
        ),
      );
    }
  };

  return (
    <>
      {/* Overlay: الخلفية الشفافة */}
      <div
        className={`fixed inset-0 bg-black/30 z-40 transition-opacity duration-300 ease-in-out ${
          isOpen ? "opacity-100" : "opacity-0 pointer-events-none"
        }`}
        onClick={onClose}
      />

      {/* Drawer: القائمة الجانبية */}
      <div
        className={`fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 p-6 flex flex-col transform transition-transform duration-300 ease-in-out overflow-y-auto ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <div className="flex justify-between items-center mb-6 flex-shrink-0">
          <h2 className="text-xl font-bold text-gray-700">My Profile</h2>
          <button
            onClick={onClose}
            className="p-1 hover:bg-gray-100 rounded-full"
          >
            <X size={20} />
          </button>
        </div>

        {/* Profile Image Upload */}
        <div className="flex flex-col items-center mb-6 flex-shrink-0">
          <label className="relative cursor-pointer group">
            <div className="w-28 h-28 rounded-full border-4 border-[#72A6BB] flex items-center justify-center overflow-hidden bg-gray-50 transition-all duration-300 group-hover:border-[#5a8799]">
              {userImage ? (
                <img
                  src={userImage}
                  alt="Profile"
                  className="w-full h-full object-cover"
                />
              ) : (
                <User size={56} className="text-gray-300" />
              )}
            </div>
            <div className="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <Camera className="text-white" size={28} />
            </div>
            <input
              type="file"
              className="hidden"
              accept="image/*"
              onChange={handleImageChange}
            />
          </label>
          <p className="mt-2 text-sm text-gray-600 font-medium">
            Click to change photo
          </p>
        </div>

        {/* Profile Info Fields */}
        <div className="space-y-4 flex-1 pb-6">
          <div>
            <label className="text-xs text-gray-500 block mb-1">
              Full Name
            </label>
            <input
              type="text"
              value={nameInput}
              onChange={(e) => setNameInput(e.target.value)}
              className="w-full p-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#72A6BB] transition outline-none"
            />
          </div>

          <div>
            <label className="text-xs text-gray-500 block mb-1">
              Email Address
            </label>
            <input
              type="email"
              value={userEmail || ""}
              readOnly
              className="w-full p-2.5 text-sm border rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed"
            />
          </div>

          <div>
            <label className="text-xs text-gray-500 block mb-1">
              Birth Date
            </label>
            <input
              type="date"
              value={birthDateInput}
              onChange={(e) => setBirthDateInput(e.target.value)}
              className="w-full p-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#72A6BB] transition outline-none bg-white text-gray-700"
            />
          </div>

          <div>
            <label className="text-xs text-gray-500 block mb-1">Gender</label>
            <select
              value={genderInput}
              onChange={(e) => setGenderInput(e.target.value)}
              className="w-full p-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#72A6BB] transition outline-none bg-white text-gray-700"
            >
              <option value="" disabled>
                Select Gender
              </option>
              <option value="male">male</option>
              <option value="female">female</option>
            </select>
          </div>

          <div>
            <label className="text-xs text-gray-500 block mb-1">Age</label>
            <input
              type="number"
              min="1"
              max="120"
              value={ageInput}
              onChange={(e) => setAgeInput(e.target.value)}
              className="w-full p-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#72A6BB] transition outline-none"
            />
          </div>

          <div>
            <label className="text-xs text-gray-500 block mb-1">Role</label>
            <input
              type="text"
              value={userRole || ""}
              readOnly
              className="w-full p-2.5 text-sm border rounded-xl bg-gray-50 text-gray-500 font-medium cursor-not-allowed"
            />
          </div>
        </div>

        {/* Action Button */}
        <div className="pt-2 flex-shrink-0 bg-white">
          <button
            onClick={handleSave}
            className="w-full bg-[#72A6BB] text-white py-3 rounded-xl font-semibold text-base shadow-md hover:bg-[#5a8799] transition"
          >
            Save Changes
          </button>
        </div>
      </div>
    </>
  );
};

export default ProfileDrawer;
