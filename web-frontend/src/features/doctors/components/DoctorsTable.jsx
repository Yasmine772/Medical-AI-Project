import { useEffect } from "react";
import { useSelector, useDispatch } from "react-redux";
import { fetchApprovedDoctors } from "../doctorsSlice";
import api from "../../../api/axios";
const DoctorsTable = () => {
  const dispatch = useDispatch();
  const { approved, loading } = useSelector((state) => state.doctors);

  // جلب الأطباء عند فتح الصفحة مع إرسال التوكن تلقائياً عبر الـ Interceptor
  useEffect(() => {
    dispatch(fetchApprovedDoctors());
  }, [dispatch]);

  const getDoctorPhotoUrl = (photoPath) => {
    if (!photoPath) return "https://via.placeholder.com/40";
    if (photoPath.startsWith("http")) return photoPath;
    // إذا لم تكن تحتوي على storage/ في البداية، قد تحتاج لإضافتها حسب إعدادات الباك إند لديك
    return `${api.defaults.baseURL}/storage/${photoPath}`;
    // أو إذا كان الـ API يخزنها مباشرة بدون storage/ ارجعي للرابط السابق:
    // return `${api.defaults.baseURL}/${photoPath}`;
  };

  return (
    <div className="bg-white/30 backdrop-blur-md rounded-3xl border border-white/50 p-6 mt-6 shadow-sm">
      <h2 className="text-xl font-bold mb-6 text-gray-700">
        Doctors Directory
      </h2>

      <table className="w-full text-left border-collapse">
        <thead>
          <tr className="text-gray-400 text-sm border-b border-white/30">
            <th className="pb-4">Doctor Photo</th>
            <th className="pb-4">Name</th>
            <th className="pb-4">Email</th>
            <th className="pb-4">Phone</th>
            <th className="pb-4">Specialization</th>
          </tr>
        </thead>
        <tbody className="text-gray-700">
          {loading ? (
            <tr>
              <td colSpan="5" className="py-6 text-center text-gray-500">
                Loading doctors...
              </td>
            </tr>
          ) : approved.length > 0 ? (
            approved.map((doc) => (
              <tr key={doc.id} className="border-b border-white/20">
                <td className="py-4">
                  <img
                    src={getDoctorPhotoUrl(doc.photo)}
                    alt={`${doc.full_name}'s profile`}
                    className="w-20 h-20 rounded-full object-cover"
                  />
                </td>
                <td className="py-4 font-semibold">{doc.full_name}</td>
                <td className="py-4 text-gray-600">{doc.email}</td>
                <td className="py-4 text-gray-600">{doc.phone}</td>
                <td className="py-4">{doc.specialization}</td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="5" className="py-6 text-center text-gray-500">
                No approved doctors yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
};

export default DoctorsTable;
