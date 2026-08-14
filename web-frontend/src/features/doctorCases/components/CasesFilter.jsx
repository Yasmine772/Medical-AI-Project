const CasesFilter = ({ selectedFilter, setSelectedFilter, selectedTimeFilter, setSelectedTimeFilter }) => {
  return (
    <div className="flex gap-3">
      
      <select 
        value={selectedFilter}
        onChange={(e) => setSelectedFilter(e.target.value)}
        className="text-xs p-2.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-md text-[#2c2c2a] outline-none shadow-sm cursor-pointer"
      >
        <option value="all">All Cases</option>
        <option value="urgent">Urgent</option>
        <option value="pending">Pending Review</option>
        <option value="completed">Completed</option>
      </select>

      
      <select 
        value={selectedTimeFilter}
        onChange={(e) => setSelectedTimeFilter(e.target.value)}
        className="text-xs p-2.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-md text-[#2c2c2a] outline-none shadow-sm cursor-pointer"
      >
        <option value="today">Today</option>
        <option value="last_7_days">Last 7 Days</option>
        <option value="last_30_days">Last 30 Days</option>
      </select>
    </div>
  );
};

export default CasesFilter;