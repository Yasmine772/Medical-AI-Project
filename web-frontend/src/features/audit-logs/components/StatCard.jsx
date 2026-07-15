const StatCard = ({ title, value }) => {
  return (
    <div className="bg-white/30 backdrop-blur-md border border-white/20 p-6 rounded-3xl flex flex-col items-center justify-center shadow-lg transition-transform hover:scale-105">
      <span className="text-4xl font-black text-[#72A6BB]">{value}</span>
      <span className="text-sm font-medium text-black/80 mt-1">{title}</span>
    </div>
  );
};

export default StatCard;
