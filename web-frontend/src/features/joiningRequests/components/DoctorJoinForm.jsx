import { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { sendJoinRequest, clearStatus } from "../joiningRequestsSlice";

import {
  User,
  Phone,
  Mail,
  Lock,
  Stethoscope,
  Award,
  Building2,
  FileText,
  FileCheck,
  Send,
  AlertCircle,
  CheckCircle2,
} from "lucide-react";

const SPECIALIZATIONS = [
  "Allergist", "Allergy Specialist", "Cardiologist", "Dentist", "Dermatologist",
  "Endocrinologist", "ENT Specialist", "Eye Specialist", "Gastroenterologist",
  "General Physician", "General Practitioner", "Gynaecologist", "Gynecologist",
  "Health Care Physician", "Hepatologist", "HIV Specialist", "Immunologist",
  "Infectious Disease Specialist", "Nephrologist", "Anesthesiologist", "Neurologist",
  "Neurosurgeon", "Nutritionist", "Oncologist", "Ophthalmic Surgeon", "Ophthalmologist",
  "Optometrist", "Orthopedic Surgeon", "Otorhinolaryngologist", "Pathologist", "Pediatrician",
  "Pharmacist", "Physician", "Psychiatrist", "Pulmonologist", "Renal Specialist",
  "Rheumatologist", "Skin Specialist", "Sleep Specialist", "Specialist", "Surgeon",
  "Technician", "Therapist", "Urologist",
];

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
      className="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-xl shadow-gray-100 relative overflow-hidden"
    >
     
      <div className="mb-6 pb-4 border-b border-gray-100">
        <div className="text-[18px] font-extrabold text-gray-900 mb-1 flex items-center gap-2">
          <Stethoscope className="w-5 h-5 text-[#72A6BB]" />
          طلب انضمام طبيب
        </div>
        <div className="text-[13px] text-gray-500">
          سنتواصل معك خلال ٢٤-٤٨ ساعة بعد مراجعة طلبك
        </div>
      </div>

     
      {successMessage && (
        <div className="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-semibold flex items-center gap-2 animate-fadeIn">
          <CheckCircle2 className="w-4 h-4 shrink-0" />
          <span>{successMessage}</span>
        </div>
      )}

      {/* رسالة الخطأ */}
      {error && (
        <div className="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-semibold flex items-start gap-2 animate-fadeIn">
          <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
          <div>
            <span>{error.message}</span>
            {error.errors && (
              <ul className="mt-1 list-disc pr-5 space-y-0.5 font-normal">
                {Object.entries(error.errors).map(([field, msgs]) => (
                  <li key={field}>{Array.isArray(msgs) ? msgs[0] : msgs}</li>
                ))}
              </ul>
            )}
          </div>
        </div>
      )}

      {/* Section 1: Personal */}
      <div className="mb-6">
        <div className="text-[11px] font-bold text-gray-400 tracking-wider mb-4 pb-2 border-b border-gray-100 uppercase flex items-center gap-1.5">
          <User className="w-3.5 h-3.5 text-[#72A6BB]" />
          المعلومات الشخصية
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mb-3.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              الاسم الكامل <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <User className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="full_name"
                value={formData.full_name}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="text"
                placeholder="د. محمد الأحمد"
                required
              />
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              رقم الهاتف <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <Phone className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="tel"
                placeholder="09xxxxxxxx"
                required
              />
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              البريد الإلكتروني <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <Mail className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="email"
                value={formData.email}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="email"
                placeholder="doctor@example.com"
                required
              />
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              كلمة المرور <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <Lock className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="password"
                value={formData.password}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="password"
                placeholder="••••••••"
                required
              />
            </div>
          </div>
        </div>
      </div>

      {/* Section 2: Professional */}
      <div className="mb-6">
        <div className="text-[11px] font-bold text-gray-400 tracking-wider mb-4 pb-2 border-b border-gray-100 uppercase flex items-center gap-1.5">
          <Award className="w-3.5 h-3.5 text-[#72A6BB]" />
          المعلومات المهنية
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mb-3.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              التخصص <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <Stethoscope className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <select
                name="specialization"
                value={formData.specialization}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300 cursor-pointer appearance-none"
                required
              >
                <option value="">اختر التخصص</option>
                {SPECIALIZATIONS.map((spec) => (
                  <option key={spec} value={spec}>
                    {spec}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              سنوات الخبرة <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <Award className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <select
                name="years_of_experience"
                value={formData.years_of_experience}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300 cursor-pointer appearance-none"
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
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mb-3.5">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold">
              هاتف العيادة / العمل
            </label>
            <div className="relative flex items-center">
              <Building2 className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="clinic_phone"
                value={formData.clinic_phone}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="text"
                placeholder="رقم هاتف العيادة"
              />
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center gap-1">
              رقم الرخصة <span className="text-rose-500">*</span>
            </label>
            <div className="relative flex items-center">
              <FileCheck className="absolute right-3 w-4 h-4 text-gray-400 pointer-events-none" />
              <input
                name="license_number"
                value={formData.license_number}
                onChange={handleChange}
                className="w-full pr-10 pl-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300"
                type="text"
                placeholder="رقم ترخيص مزاولة المهنة"
                required
              />
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-[12px] text-gray-700 font-semibold">
            نبذة تعريفية (Biography)
          </label>
          <textarea
            name="biography"
            value={formData.biography}
            onChange={handleChange}
            className="w-full p-3.5 rounded-xl border border-gray-200 bg-gray-50/50 text-black text-[13px] outline-none focus:border-[#72A6BB] focus:bg-white focus:ring-2 focus:ring-[#72A6BB]/10 transition-all duration-300 resize-none"
            placeholder="اكتب نبذة قصيرة عن خبرتك الطبية..."
            rows="3"
          ></textarea>
        </div>
      </div>

      {/* Section 3: Documents */}
      <div className="mb-6">
        <div className="text-[11px] font-bold text-gray-400 tracking-wider mb-4 pb-2 border-b border-gray-100 uppercase flex items-center gap-1.5">
          <FileText className="w-3.5 h-3.5 text-[#72A6BB]" />
          الوثائق المطلوبة
        </div>

        <div className="flex flex-col gap-3">
          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center justify-between">
              <span>
                إجازة مزاولة المهنة (License File){" "}
                <span className="text-rose-500">*</span>
              </span>
            </label>
            <input
              type="file"
              name="license_file"
              onChange={handleFileChange}
              accept=".pdf"
              className="file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#72A6BB]/15 file:text-[#72A6BB] hover:file:bg-[#72A6BB] hover:file:text-white file:transition-all p-2 rounded-xl border border-gray-200 bg-gray-50/50 text-xs text-gray-500 cursor-pointer"
              required
            />
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center justify-between">
              <span>
                السيرة الذاتية (CV File){" "}
                <span className="text-rose-500">*</span>
              </span>
            </label>
            <input
              type="file"
              name="cv_file"
              onChange={handleFileChange}
              accept=".pdf"
              className="file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#72A6BB]/15 file:text-[#72A6BB] hover:file:bg-[#72A6BB] hover:file:text-white file:transition-all p-2 rounded-xl border border-gray-200 bg-gray-50/50 text-xs text-gray-500 cursor-pointer"
              required
            />
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[12px] text-gray-700 font-semibold flex items-center justify-between">
              <span>صورة شخصية (Photo)</span>
            </label>
            <input
              type="file"
              name="photo"
              onChange={handleFileChange}
              accept="image/*"
              className="file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#72A6BB]/15 file:text-[#72A6BB] hover:file:bg-[#72A6BB] hover:file:text-white file:transition-all p-2 rounded-xl border border-gray-200 bg-gray-50/50 text-xs text-gray-500 cursor-pointer"
            />
          </div>
        </div>
      </div>

      
      <button
        type="submit"
        disabled={loading}
        className="w-full py-3.5 rounded-xl bg-[#72A6BB] text-white font-bold text-[14px] mt-2 hover:bg-[#5e8d9f] transition-all duration-300 hover:shadow-lg hover:shadow-[#72A6BB]/25 cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 active:scale-[0.99]"
      >
        <Send className="w-4 h-4" />
        {loading ? "جاري الإرسال..." : "إرسال طلب الانضمام"}
      </button>
    </form>
  );
}
