import { useState, useRef, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchDoctorProfile,
  updateDoctorProfile,
} from "../../../store/authSlice";

const ProfilePanel = ({ isOpen, onClose }) => {
  const dispatch = useDispatch();
  const { doctorProfile, loading } = useSelector((state) => state.auth);

  const fileInputRef = useRef(null);
  const cvInputRef = useRef(null);
  const licenseInputRef = useRef(null);

  const [localImage, setLocalImage] = useState(null);
  const [selectedFile, setSelectedFile] = useState(null);
  const [selectedCv, setSelectedCv] = useState(null);
  const [selectedLicense, setSelectedLicense] = useState(null);

  useEffect(() => {
    if (isOpen) {
      dispatch(fetchDoctorProfile());
    }
  }, [isOpen, dispatch]);

  const handleClose = () => {
    setLocalImage(null);
    setSelectedFile(null);
    setSelectedCv(null);
    setSelectedLicense(null);
    onClose();
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedFile(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setLocalImage(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleCvChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedCv(file);
    }
  };

  const handleLicenseChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedLicense(file);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const data = new FormData();

    const formElements = e.target.elements;
    const fields = [
      "full_name",
      "email",
      "phone",
      "specialization",
      "years_of_experience",
      "license_number",
      "clinic_phone",
      "clinic_address",
      "biography",
    ];

    fields.forEach((field) => {
      if (formElements[field] && formElements[field].value !== "") {
    
        data.append(field, formElements[field].value);
      }
    });

    if (selectedFile) {
      data.append("photo", selectedFile);
    }
    if (selectedCv) {
      data.append("cv_file", selectedCv);
    }
    if (selectedLicense) {
      data.append("license_file", selectedLicense);
    }

    dispatch(updateDoctorProfile(data)).then((res) => {
      if (!res.error) {
        alert("Profile updated successfully!");
        onClose();
      }
    });
  };

  const displayedImage = localImage || doctorProfile?.photo;

  return (
    <>
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 transition-opacity"
          onClick={handleClose}
        ></div>
      )}

      <div
        className={`fixed top-0 right-0 h-full w-full md:w-2/3 lg:w-1/2 bg-white/90 backdrop-blur-2xl shadow-2xl z-50 transform transition-transform duration-500 ease-in-out border-l border-white/50 overflow-y-auto ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <div className="p-8">
          <button
            onClick={handleClose}
            className="mb-6 text-gray-400 hover:text-gray-800 transition-colors font-bold flex items-center gap-1"
          >
            ✕ Close
          </button>

          <h2 className="text-3xl font-bold text-gray-800 mb-6">
            Doctor Profile
          </h2>

          {loading && !doctorProfile ? (
            <div className="flex justify-center items-center h-64">
              <p className="text-[#72A6BB] font-medium animate-pulse">
                Loading profile...
              </p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4 pb-12">
              <div className="flex flex-col items-center mb-6">
                <div
                  className="w-32 h-32 rounded-full bg-gray-200 border-4 border-white shadow-lg cursor-pointer overflow-hidden relative group"
                  onClick={() => fileInputRef.current.click()}
                >
                  {displayedImage ? (
                    <img
                      src={displayedImage}
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

              {/* الحقول النصية */}
              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Full Name
                </label>
                <input
                  type="text"
                  name="full_name"
                  defaultValue={doctorProfile?.full_name || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Email Address
                </label>
                <input
                  type="email"
                  name="email"
                  defaultValue={doctorProfile?.email || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Phone Number
                </label>
                <input
                  type="text"
                  name="phone"
                  defaultValue={doctorProfile?.phone || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Specialization
                </label>
                <input
                  type="text"
                  name="specialization"
                  defaultValue={doctorProfile?.specialization || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Years of Experience
                </label>
                <input
                  type="number"
                  name="years_of_experience"
                  defaultValue={doctorProfile?.years_of_experience || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  License Number
                </label>
                <input
                  type="text"
                  name="license_number"
                  defaultValue={doctorProfile?.license_number || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Clinic Phone
                </label>
                <input
                  type="text"
                  name="clinic_phone"
                  defaultValue={doctorProfile?.clinic_phone || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Clinic Address
                </label>
                <input
                  type="text"
                  name="clinic_address"
                  defaultValue={doctorProfile?.clinic_address || ""}
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1"
                />
              </div>

              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm">
                <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider">
                  Biography
                </label>
                <textarea
                  name="biography"
                  defaultValue={doctorProfile?.biography || ""}
                  rows="3"
                  className="w-full bg-transparent font-semibold text-gray-800 outline-none mt-1 resize-none"
                />
              </div>

              {/* ملف الـ CV */}
              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm flex items-center justify-between">
                <div>
                  <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider block">
                    CV Document
                  </label>
                  <span className="text-sm font-medium text-gray-700 truncate max-w-[150px] block">
                    {selectedCv
                      ? selectedCv.name
                      : doctorProfile?.cv_file
                        ? "Uploaded CV File"
                        : "No CV uploaded"}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  {/* زر المعاينة يظهر دائماً إذا كان هناك رابط قديم أو تم اختيار ملف جديد */}
                  {(doctorProfile?.cv_file || selectedCv) && (
                    <a
                      href={
                        selectedCv
                          ? URL.createObjectURL(selectedCv)
                          : doctorProfile.cv_file
                      }
                      target="_blank"
                      rel="noopener noreferrer"
                      className="px-3 py-2 bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-300 transition-colors"
                    >
                      View
                    </a>
                  )}
                  <button
                    type="button"
                    onClick={() => cvInputRef.current.click()}
                    className="px-3 py-2 bg-[#72A6BB] text-white rounded-xl text-xs font-semibold hover:bg-[#5e8d9e] transition-colors"
                  >
                    {doctorProfile?.cv_file || selectedCv
                      ? "Replace"
                      : "Upload"}
                  </button>
                  <input
                    type="file"
                    ref={cvInputRef}
                    onChange={handleCvChange}
                    className="hidden"
                    accept=".pdf,.doc,.docx"
                  />
                </div>
              </div>

              {/* ملف الرخصة */}
              <div className="p-4 bg-white/60 rounded-2xl border border-white/85 shadow-sm flex items-center justify-between">
                <div>
                  <label className="text-[10px] text-[#72A6BB] font-bold uppercase tracking-wider block">
                    License Document
                  </label>
                  <span className="text-sm font-medium text-gray-700 truncate max-w-[150px] block">
                    {selectedLicense
                      ? selectedLicense.name
                      : doctorProfile?.license_file
                        ? "Uploaded License File"
                        : "No license uploaded"}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  {(doctorProfile?.license_file || selectedLicense) && (
                    <a
                      href={
                        selectedLicense
                          ? URL.createObjectURL(selectedLicense)
                          : doctorProfile.license_file
                      }
                      target="_blank"
                      rel="noopener noreferrer"
                      className="px-3 py-2 bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-300 transition-colors"
                    >
                      View
                    </a>
                  )}
                  <button
                    type="button"
                    onClick={() => licenseInputRef.current.click()}
                    className="px-3 py-2 bg-[#72A6BB] text-white rounded-xl text-xs font-semibold hover:bg-[#5e8d9e] transition-colors"
                  >
                    {doctorProfile?.license_file || selectedLicense
                      ? "Replace"
                      : "Upload"}
                  </button>
                  <input
                    type="file"
                    ref={licenseInputRef}
                    onChange={handleLicenseChange}
                    className="hidden"
                    accept=".pdf,.doc,.docx,image/*"
                  />
                </div>
              </div>

              {/* زر الحفظ */}
              <div className="pt-4">
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3 bg-[#72A6BB] text-white font-semibold rounded-2xl shadow-lg hover:bg-[#5e8d9e] transition-colors flex items-center justify-center gap-2"
                >
                  {loading ? "Saving..." : "Save Changes"}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </>
  );
};

export default ProfilePanel;
