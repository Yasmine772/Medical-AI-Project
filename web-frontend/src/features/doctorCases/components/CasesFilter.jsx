const CasesFilter = ({ selectedFilter, setSelectedFilter, selectedTimeFilter, setSelectedTimeFilter }) => {
  return (
    <div className="flex gap-3">
      <select 
        value={selectedFilter}
        onChange={(e) => setSelectedFilter(e.target.value)}
        className="text-xs p-2.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-md text-[#2c2c2a] outline-none"
      >
        <option value="all">All Cases</option>
        <option value="urgent">Urgent</option>
        <option value="pending">Pending Review</option>
        <option value="done">Completed</option>
      </select>

      <select 
        value={selectedTimeFilter}
        onChange={(e) => setSelectedTimeFilter(e.target.value)}
        className="text-xs p-2.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-md text-[#2c2c2a] outline-none"
      >
        <option value="today">Today</option>
        <option value="week">Last 7 Days</option>
        <option value="month">Last 30 Days</option>
      </select>
    </div>
  );
};

export default CasesFilter;