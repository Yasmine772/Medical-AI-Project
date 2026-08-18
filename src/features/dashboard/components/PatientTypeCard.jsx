const PatientTypeCard = () => {
  return (
    <div className="!bg-white/30 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100">
      <h3 className="text-sm text-gray-500 mb-4">Type of Patients</h3>
      <div className="flex justify-between text-center">
        <div>
          <p className="text-xs text-gray-400">NOW</p>
          <p className="text-2xl font-bold">10</p>
        </div>
        <div className="w-px bg-gray-200" /> {/* خط فاصل */}
        <div>
          <p className="text-xs text-gray-400">REGULAR</p>
          <p className="text-2xl font-bold">10</p>
        </div>
      </div>
    </div>
  );
};
export default PatientTypeCard;