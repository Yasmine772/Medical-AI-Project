import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchDoctorMonthlyProfits,
  fetchDoctorDailyProfits,
} from "../doctorDashboardSlice";

const EarningsSummary = () => {
  const dispatch = useDispatch();

  const { monthlyProfits, dailyProfits } = useSelector(
    (state) => state.doctorDashboard,
  );

  useEffect(() => {
    dispatch(fetchDoctorMonthlyProfits());
    dispatch(fetchDoctorDailyProfits());
  }, [dispatch]);

 
  const monthlyDisplay =
    monthlyProfits?.monthly_display || monthlyProfits?.data?.monthly_display;
  const dailyDisplay =
    dailyProfits?.daily_display || dailyProfits?.data?.daily_display;
  const currency = monthlyProfits?.currency || monthlyProfits?.data?.currency;

  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm relative overflow-hidden transition-all duration-300 hover:shadow-md">
      {/* Decorative accent background blob */}
      <div
        className="absolute -right-8 -top-8 w-24 h-24 rounded-full opacity-10 pointer-events-none"
        style={{ backgroundColor: "#72A6BB" }}
      ></div>

      {/* Header */}
      <div className="flex justify-between items-center mb-5">
        <h3 className="font-bold text-gray-800 text-base">Earnings Overview</h3>
        <span
          className="text-xs font-semibold px-2.5 py-1 rounded-full text-white shadow-sm"
          style={{ backgroundColor: "#72A6BB" }}
        >
          Active
        </span>
      </div>

      <div className="space-y-4">
        {/* Monthly Earnings Row */}
        <div className="flex justify-between items-center text-sm p-3 rounded-2xl bg-white/50 border border-white/60">
          <span className="text-gray-600 font-medium">This Month</span>
          <span className="font-bold text-lg" style={{ color: "#72A6BB" }}>
            {monthlyDisplay ? monthlyDisplay : "Loading..."}
          </span>
        </div>

        {/* Daily Earnings Row */}
        <div className="flex justify-between items-center text-sm p-3 rounded-2xl bg-white/50 border border-white/60">
          <span className="text-gray-600 font-medium">Today's Earnings</span>
          <span className="font-bold text-base text-gray-700">
            {dailyDisplay ? dailyDisplay : "Loading..."}
          </span>
        </div>
      </div>

      {/* Footer / Currency Note */}
      <div className="mt-5 pt-3 border-t border-gray-200/60 flex justify-between items-center text-xs text-gray-400">
        <span>Updated just now</span>
        <span
          className="uppercase font-medium tracking-wider"
          style={{ color: "#72A6BB" }}
        >
          {currency ? currency : "USD"}
        </span>
      </div>
    </div>
  );
};

export default EarningsSummary;
