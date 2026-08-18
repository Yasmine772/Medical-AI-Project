const PatientTypeCard = ({ now, regular }) => {
  return (
    <div className="bg-white/40 backdrop-blur-md p-6 rounded-[32px] border border-white/50 shadow-sm">
      <h3 className="text-gray-500 font-bold text-sm mb-4">Type of Patients</h3>
      <div className="flex justify-between">
        <div>
          <p className="text-gray-400 text-xs uppercase">Now</p>
          <p className="text-2xl font-bold text-gray-800">{now}</p>
        </div>
        <div>
          <p className="text-gray-400 text-xs uppercase">Regular</p>
          <p className="text-2xl font-bold text-gray-800">{regular}</p>
        </div>
      </div>
    </div>
  );
};
export default PatientTypeCard;
