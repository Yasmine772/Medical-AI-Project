const AuditFilters = () => {
  return (
    <div className="flex flex-wrap gap-4 p-4 bg-white/40 backdrop-blur-xl rounded-3xl border border-white/40 shadow-sm items-center">
      <input
        type="text"
        placeholder="Search in details..."
        className="flex-[2] min-w-[200px] bg-white/60 border border-gray-300 rounded-xl px-4 py-2.5 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-[#72A6BB] outline-none"
      />

      {/* 2.Categories */}
      <select className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer">
        <option>All Categories</option>
        <option>Database</option>
        <option>Doctors</option>
      </select>

      {/* 3.Operations */}
      <select className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer">
        <option>All Operations</option>
        <option>Create</option>
        <option>Update</option>
      </select>

      {/* 4.Date */}
      <select className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer">
        <option>Today</option>
        <option>Last 7 Days</option>
        <option>Last Month</option>
      </select>
    </div>
  );
};

export default AuditFilters;
