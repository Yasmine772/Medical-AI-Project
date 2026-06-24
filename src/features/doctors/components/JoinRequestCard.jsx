// أضفنا onApprove و onReject للـ props
const JoinRequestCard = ({ doctor, onApprove, onReject }) => {
  return (
    <div className="flex items-center justify-between p-6 mb-4 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
      <div className="flex items-center gap-6">
        <img
          src={doctor.image || "/profile-photo.jpg"}
          alt={doctor.name}
          className="w-24 h-24 aspect-square rounded-full object-cover border border-gray-200"
        />
        <div className="flex flex-col gap-1">
          <h3 className="font-bold text-lg text-gray-800">{doctor.name}</h3>
          <p className="text-sm text-gray-600">
            <span className="font-semibold">Qualification:</span>{" "}
            {doctor.qualification}
          </p>
          <p className="text-sm text-gray-600">
            <span className="font-semibold">Specialization:</span>{" "}
            {doctor.specialization}
          </p>
          <p className="text-sm text-[#72A6BB] font-medium mt-1">
            📞 {doctor.phone || "No phone provided"}
          </p>
        </div>
      </div>

      {/* الأزرار بعد الربط */}
      <div className="flex items-center gap-3">
        <button
          onClick={onApprove} // ربط دالة الموافقة
          className="px-6 py-2 bg-[#72A6BB] text-white rounded-full font-semibold hover:bg-[#5f8d9f] transition"
        >
          Approve
        </button>
        <button
          onClick={onReject} // ربط دالة الرفض
          className="px-6 py-2 bg-gray-100 text-gray-600 rounded-full font-semibold hover:bg-gray-200 transition"
        >
          Reject
        </button>
      </div>
    </div>
  );
};

export default JoinRequestCard;
