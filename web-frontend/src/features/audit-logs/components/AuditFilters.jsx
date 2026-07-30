import { useState } from "react";

const AuditFilters = ({ onFilterChange }) => {
  const [filters, setFilters] = useState({
    operation: "",
    category: "",
    date: "",
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    const newFilters = { ...filters, [name]: value };
    setFilters(newFilters);
    onFilterChange(newFilters);
  };

  return (
    <div className="flex flex-wrap gap-4 p-4 bg-white/40 backdrop-blur-xl rounded-3xl border border-white/40 shadow-sm items-center">
      {/* 1. Category */}
      <select
        name="category"
        className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer"
        onChange={handleChange}
      >
        <option value="">All Categories</option>
        <option value="App\Models\User">Users</option>

        {/* أضف باقي الموديلات هنا بنفس طريقة الـ value */}
      </select>

      {/* 2. Operation */}
      <select
        name="operation"
        className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer"
        onChange={handleChange}
      >
        <option value="">All Operations</option>
        <option value="created">Create</option>
        <option value="updated">Update</option>
        <option value="deleted">Delete</option>
      </select>

      {/* 3. Date */}
      <select
        name="date"
        className="flex-1 bg-white/60 border border-gray-300 rounded-xl px-3 py-2.5 text-gray-700 outline-none cursor-pointer"
        onChange={handleChange}
      >
        <option value="">Select Date</option>
        <option value="today">Today</option>
        <option value="last_7_days">Last 7 Days</option>
      </select>
    </div>
  );
};

export default AuditFilters;
