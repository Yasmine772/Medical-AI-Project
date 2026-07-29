import { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { sendJoinRequest, clearStatus } from "../joiningRequestsSlice";

export default function DoctorJoinForm() {
  const dispatch = useDispatch();
  const { loading, successMessage, error } = useSelector(
    (state) => state.joiningRequests,
  );

  useEffect(() => {
    dispatch(clearStatus());
  }, [dispatch]);

  const [formData, setFormData] = useState({
    full_name: "",
    email: "",
    password: "",
    phone: "",
    specialization: "",
    years_of_experience: "",
    clinic_phone: "",
    license_number: "",
    biography: "",
  });

  const [files, setFiles] = useState({
    photo: null,
    license_file: null,
    cv_file: null,
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleFileChange = (e) => {
    setFiles({ ...files, [e.target.name]: e.target.files[0] });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const data = new FormData();

    
    Object.keys(formData).forEach((key) => {
      data.append(key, formData[key]);
    });

  
    if (files.photo) data.append("photo", files.photo);
    if (files.license_file) data.append("license_file", files.license_file);
    if (files.cv_file) data.append("cv_file", files.cv_file);

    dispatch(sendJoinRequest(data));
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="bg-gray-50 border border-gray-200 rounded-[14px] p-7"
    >
      <div className="text-[17px] font-bold text-black mb-1">
        طلب انضمام طبيب
      </div>
      <div className="text-[12px] text-gray-500 mb-5.5">
        سنتواصل معك خلال ٢٤-٤٨ ساعة بعد مراجعة طلبك
      </div>

      {successMessage && (
        <div className="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
          {successMessage}
        </div>
      )}

      {error && (
        <div className="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
          {error}
        </div>
      )}

      {/* Section 1: Personal */}
      <div className="mb-5">
        <div className="text-[11px] font-semibold text-gray-500 tracking-wide mb-3 pb-2 border-b border-gray-200 uppercase">
          المعلومات الشخصية
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-2.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              الاسم الكامل <span className="text-red-600">*</span>
            </label>
            <input
              name="full_name"
              value={formData.full_name}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="text"
              placeholder="د. محمد الأحمد"
              required
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              رقم الهاتف <span className="text-red-600">*</span>
            </label>
            <input
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="tel"
              placeholder="09xxxxxxxx"
              required
            />
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-2.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              البريد الإلكتروني <span className="text-red-600">*</span>
            </label>
            <input
              name="email"
              value={formData.email}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="email"
              placeholder="doctor@example.com"
              required
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              كلمة المرور <span className="text-red-600">*</span>
            </label>
            <input
              name="password"
              value={formData.password}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="password"
              placeholder="••••••••"
              required
            />
          </div>
        </div>
      </div>

      {/* Section 2: Professional */}
      <div className="mb-5">
        <div className="text-[11px] font-semibold text-gray-500 tracking-wide mb-3 pb-2 border-b border-gray-200 uppercase">
          المعلومات المهنية
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-2.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              التخصص <span className="text-red-600">*</span>
            </label>
            <select
              name="specialization"
              value={formData.specialization}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              required
            >
              <option value="">اختر التخصص</option>
              <option value="أمراض جلدية">أمراض جلدية</option>
              <option value="أمراض باطنية">أمراض باطنية</option>
              <option value="أطفال">أطفال</option>
              <option value="قلبية وأوعية دموية">قلبية وأوعية دموية</option>
              <option value="عظام ومفاصل">عظام ومفاصل</option>
              <option value="طب عام">طب عام</option>
              <option value="أخرى">أخرى</option>
            </select>
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              سنوات الخبرة <span className="text-red-600">*</span>
            </label>
            <select
              name="years_of_experience"
              value={formData.years_of_experience}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              required
            >
              <option value="">اختر</option>
              <option value="1">سنة واحدة</option>
              <option value="2">سنتان</option>
              <option value="3">٣ سنوات</option>
              <option value="5">٥ سنوات</option>
              <option value="10">١٠ سنوات أو أكثر</option>
            </select>
          </div>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              هاتف العيادة / العمل
            </label>
            <input
              name="clinic_phone"
              value={formData.clinic_phone}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="text"
              placeholder="رقم هاتف العيادة"
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              رقم الرخصة <span className="text-red-600">*</span>
            </label>
            <input
              name="license_number"
              value={formData.license_number}
              onChange={handleChange}
              className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
              type="text"
              placeholder="رقم ترخيص مزاولة المهنة"
              required
            />
          </div>
        </div>
        <div className="flex flex-col gap-1.5 mt-2.5">
          <label className="text-[12px] text-gray-700 font-semibold">
            نبذة تعريفية (Biography)
          </label>
          <textarea
            name="biography"
            value={formData.biography}
            onChange={handleChange}
            className="p-2.5 rounded-lg border border-gray-300 bg-white text-black text-[13px] outline-none focus:border-[#72A6BB]"
            placeholder="اكتب نبذة قصيرة عن خبرتك الطبية..."
            rows="2"
          ></textarea>
        </div>
      </div>

      {/* Section 3: Documents */}
      <div className="mb-5">
        <div className="text-[11px] font-semibold text-gray-500 tracking-wide mb-3 pb-2 border-b border-gray-200 uppercase">
          الوثائق المطلوبة
        </div>

        <div className="flex flex-col gap-1.5 mb-3">
          <label className="text-[12px] text-gray-700 font-semibold">
            إجازة مزاولة المهنة (License File){" "}
            <span className="text-red-600">*</span>
          </label>
          <input
            type="file"
            name="license_file"
            onChange={handleFileChange}
            className="p-2 rounded-lg border border-gray-300 bg-white text-xs"
            required
          />
        </div>

        <div className="flex flex-col gap-1.5 mb-3">
          <label className="text-[12px] text-gray-700 font-semibold">
            السيرة الذاتية (CV File) <span className="text-red-600">*</span>
          </label>
          <input
            type="file"
            name="cv_file"
            onChange={handleFileChange}
            className="p-2 rounded-lg border border-gray-300 bg-white text-xs"
            required
          />
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-[12px] text-gray-700 font-semibold">
            صورة شخصية (Photo)
          </label>
          <input
            type="file"
            name="photo"
            onChange={handleFileChange}
            className="p-2 rounded-lg border border-gray-300 bg-white text-xs"
          />
        </div>
      </div>

      <button
        type="submit"
        disabled={loading}
        className="w-full py-3 rounded-lg bg-[#72A6BB] text-white font-bold text-[14px] mt-4.5 hover:bg-[#5e8d9f] transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
      >
        <i className="ti ti-send text-[15px]" aria-hidden="true"></i>
        {loading ? "جاري الإرسال..." : "إرسال طلب الانضمام"}
      </button>
    </form>
  );
}
