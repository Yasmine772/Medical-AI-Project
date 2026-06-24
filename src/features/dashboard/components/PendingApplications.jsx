const PendingApplications = () => {
  return (
    <div className="!bg-white/30 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100">
      <h3 className="text-lg font-bold mb-6">Pending Doctor Applications</h3>

      <div className="space-y-4">
        {/* طلب 1 */}
        <div className="flex justify-between items-center border-b pb-4">
          <div>
            <p className="font-semibold text-gray-800">Linva Doctor</p>
            <p className="text-xs text-gray-400">Application</p>
          </div>
          <button className="bg-[#72A6BB]/20 text-[#72A6BB] px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-200">
            Approve
          </button>
        </div>

        {/* طلب 2 */}
        <div className="flex justify-between items-center">
          <div>
            <p className="font-semibold text-gray-800">Pending Doctor</p>
            <p className="text-xs text-gray-400">Application</p>
          </div>
          <button className="bg-red-100 text-red-700 px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-red-200">
            Deny
          </button>
        </div>
      </div>

      <div className="mt-6 text-xs text-gray-400 space-y-2">
        <p>
          Diesase added{" "}
          <span className="text-gray-800 font-medium">Elssases or Advice</span>{" "}
          • 3 day ago
        </p>
        <p>
          Symplome added{" "}
          <span className="text-gray-800 font-medium">Symptoma</span> • 3 day
          ago
        </p>
        <p>
          Advice added{" "}
          <span className="text-gray-800 font-medium">Symptoms or Advice</span>{" "}
          • 2 day ago
        </p>
      </div>
    </div>
  );
};

export default PendingApplications;
