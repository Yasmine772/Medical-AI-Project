const StatItem = ({ title, value }) => (
  <div className="flex flex-col items-center justify-center border-r border-[#72A6BB]/20 last:border-r-0 px-6">
    <p className="text-[#72A6BB]/70 text-xs font-semibold uppercase tracking-wider">{title}</p>
    <h3 className="text-xl font-bold text-[#72A6BB] mt-1">{value}</h3>
  </div>
);

const StatsBar = () => {
  return (
    <div className="w-full bg-white/70 backdrop-blur-md border border-white/50 rounded-3xl p-4 flex justify-between shadow-sm">
      <StatItem title="Cases Today" value="3" />
      <StatItem title="Cases This Month" value="12" />
      <StatItem title="Earnings" value="168k L.S" />
      <StatItem title="Pending Transfer" value="28k L.S" />
    </div>
  );
};

export default StatsBar;