const JoinRequestCard = ({ doctor, onViewDetails }) => {
  
  const imageUrl = doctor.photo
    ? `http://127.0.0.1:8000/storage/${doctor.photo}`
    : "/profile-photo.jpg";

  return (
    <div className="flex items-center justify-between p-6 mb-4 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
      <div className="flex items-center gap-6">
        <img
          src={imageUrl}
          alt={doctor.full_name}
          className="w-24 h-24 aspect-square rounded-full object-cover border border-gray-200"
          onError={(e) => {
           
            e.target.src = "/profile-photo.jpg";
          }}
        />
        <div className="flex flex-col gap-1">
          <h3 className="font-bold text-lg text-gray-800">
            {doctor.full_name}
          </h3>

          <p className="text-sm text-gray-600">
            <span className="font-semibold">Years of Experience:</span>{" "}
            {doctor.years_of_experience} years
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

      <div>
        <button
          onClick={() => onViewDetails(doctor.id)}
          className="px-6 py-2.5 bg-[#72A6BB] text-white rounded-xl font-semibold hover:bg-[#5f8d9f] transition shadow-sm"
        >
          Show Details
        </button>
      </div>
    </div>
  );
};

export default JoinRequestCard;
