import { Activity, Users, DollarSign, ArrowLeftRight } from "lucide-react";

const StatCard = ({ title, value, subValue, icon: Icon }) => (
  <div className="bg-white/70 backdrop-blur-md border border-white/50 p-5 rounded-3xl flex-1 shadow-sm flex flex-col justify-between h-32">
    <div className="flex items-center gap-2">
      <Icon size={16} className="text-[#72A6BB]" />
      <p className="text-[#72A6BB]/70 text-sm font-medium">{title}</p>
    </div>

    
    <h3 className="text-2xl font-bold text-gray-800">{value}</h3>

    {subValue && (
      <span className="text-xs text-[#72A6BB] bg-[#72A6BB]/10 px-2 py-1 rounded-full font-medium w-fit">
        {subValue}
      </span>
    )}
  </div>
);

const StatsRow = () => {
  return (
    <div className="flex gap-4 w-full">
      <StatCard
        title="Cases Today"
        value="3"
        subValue="2 completed | 1 pending"
        icon={Activity}
      />
      <StatCard
        title="Cases This Month"
        value="12"
        subValue="+4 from last month"
        icon={Users}
      />
      <StatCard title="Earnings" value="168k L.S" icon={DollarSign} />
      <StatCard
        title="Pending Transfer"
        value="28k L.S"
        subValue="Due: July 15"
        icon={ArrowLeftRight}
      />
    </div>
  );
};

export default StatsRow;
