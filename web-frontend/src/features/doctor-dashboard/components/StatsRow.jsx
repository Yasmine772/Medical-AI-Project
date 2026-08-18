import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Activity, Users, DollarSign } from "lucide-react";
import {
  fetchDoctorTodayCases,
  fetchDoctorMonthCases,
  fetchDoctorMonthlyProfits, 
} from "../doctorDashboardSlice";

const StatCard = ({ title, value, subValue, icon: Icon }) => (
  <div className="bg-white/75 backdrop-blur-md border border-white/50 p-5 rounded-3xl flex-1 shadow-sm flex flex-col justify-between h-32">
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
  const dispatch = useDispatch();
  const { todayCases, monthCases, monthlyProfits } = useSelector(
    (state) => state.doctorDashboard,
  );

  useEffect(() => {
    dispatch(fetchDoctorTodayCases());
    dispatch(fetchDoctorMonthCases());
    dispatch(fetchDoctorMonthlyProfits()); 
  }, [dispatch]);


  const todaySubValue = todayCases
    ? `${todayCases.completed} completed | ${todayCases.pending} pending`
    : "Loading...";

  const monthSubValue = monthCases
    ? `${monthCases.difference >= 0 ? `+${monthCases.difference}` : monthCases.difference} from last month`
    : "Loading...";


  const earningsValue = monthlyProfits
    ? monthlyProfits.monthly_display
    : "Loading...";

  return (
    <div className="flex gap-4 w-full">
      <StatCard
        title="Cases Today"
        value={todayCases ? todayCases.total : "0"}
        subValue={todaySubValue}
        icon={Activity}
      />
      <StatCard
        title="Cases This Month"
        value={monthCases ? monthCases.current_month : "0"}
        subValue={monthSubValue}
        icon={Users}
      />
     
      <StatCard title="Earnings" value={earningsValue} icon={DollarSign} />

    </div>
  );
};

export default StatsRow;
