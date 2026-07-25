const KPICard = ({ title, value, icon: Icon }) => {
  return (
    <div className="!bg-white/30 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-white/50 flex justify-between items-center">
      <div>
        <p className="text-gray-500 text-sm font-medium">{title}</p>
        <h3 className="text-2xl font-bold text-gray-800 mt-1">{value}</h3>
      </div>
      <div className="p-3 bg-[#72A6BB]/20 text-[#72A6BB] rounded-xl">
        <Icon size={24} />
      </div>
    </div>
  );
};

export default KPICard;
