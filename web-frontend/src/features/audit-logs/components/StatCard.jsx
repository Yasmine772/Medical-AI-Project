import React from "react";

const StatCard = React.memo(({ title, value, loading }) => {
  return (
    <div className="bg-white/30 backdrop-blur-md border border-white/20 p-6 rounded-3xl flex flex-col items-center justify-center shadow-lg transition-transform hover:scale-105 min-h-[140px]">
      {loading ? (
        <div className="w-10 h-6 bg-gray-200 animate-pulse rounded"></div>
      ) : (
        <span className="text-4xl font-black text-[#72A6BB]">{value}</span>
      )}
      <span className="text-sm font-medium text-black/80 mt-1">{title}</span>
    </div>
  );
});

export default StatCard;