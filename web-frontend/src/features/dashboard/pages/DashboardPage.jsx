import KPICard from "../components/KPICard";
import { Users, Activity, Stethoscope, FileText } from "lucide-react";
import TopDiseasesCard from "../components/TopDiseasesCard";
import PatientTypeCard from "../components/PatientTypeCard";
import doctorImg from "../../../assets/doctor-illustration.png";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchDashboardStats } from "../dashboardSlice";
import SessionStatusChart from "../components/SessionStatusChart";
import aiDoctorImg from "../../../assets/ai-doctors.png";
import { UserCircle } from "lucide-react";
import ProfileDrawer from "../components/ProfileDrawer";
import NotificationDropdown from "../../notifications/NotificationDropdown";
const DashboardPage = () => {
  const dispatch = useDispatch();
  const dashboardState = useSelector((state) => state.dashboard) || {};
  const { stats, loading } = dashboardState;
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  useEffect(() => {
    dispatch(fetchDashboardStats());
  }, [dispatch]);

  if (loading || !stats)
    return (
      <div className="flex justify-center items-center h-[50vh]">
        <div className="w-12 h-12 border-4 border-t-transparent border-[#72A6BB] rounded-full animate-spin"></div>
      </div>
    );

  const dateObj = new Date(stats.currentDate);
  const monthYear = dateObj.toLocaleDateString("en-US", {
    month: "long",
    year: "numeric",
  });
  const currentDay = dateObj.getDate();

  return (
    <div className="flex flex-col gap-4 overflow-y-auto">
      {/* Header */}
      <div className="flex justify-between items-center shrink-0">
        <h1 className="text-4xl font-bold text-gray-800">HOME PAGE</h1>
        <NotificationDropdown />
        <button
          onClick={() => setIsDrawerOpen(true)}
          className="p-2 bg-white rounded-full shadow-md hover:bg-gray-50 transition"
        >
          <UserCircle size={32} className="text-[#72A6BB]" />
        </button>
      </div>

      <ProfileDrawer
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />

      {/* Welcome Card & Patient Type */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        <div className="h-32 overflow-hidden rounded-[32px]">
          <img
            src={aiDoctorImg}
            alt="AI Doctors Collaboration"
            className="w-full h-full object-cover"
          />
        </div>

        {/* welcome card */}
        <div className="lg:col-span-2 bg-gradient-to-r from-[#72A6BB] to-white p-6 rounded-[32px] shadow-sm border border-gray-100 flex items-center relative overflow-hidden h-32">
          <div className="flex-1 z-10 pl-2 space-y-1">
            <h1 className="text-2xl font-semibold text-white">
              Good morning, Doc!
            </h1>
            <p className="text-white/90 text-sm md:text-base leading-tight max-w-[200px]">
              Ready for an exciting day? You've got patients lined up!
            </p>
          </div>

          <div className="absolute right-0 top-0 bottom-0 w-40 z-0">
            <img
              src={doctorImg}
              alt="Doctor"
              className="w-full h-full object-contain"
            />
          </div>
        </div>
        <div className="lg:col-span-1">
          <PatientTypeCard
            now={stats.patientStats.now}
            regular={stats.patientStats.regular}
          />
        </div>
      </div>

      {/* Four Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 shrink-0">
        <KPICard title="Active Users" value={stats.activeUsers} icon={Users} />
        <KPICard
          title="Daily Diagnoses"
          value={stats.dailyDiagnoses}
          icon={Activity}
        />
        <KPICard
          title="Active Doctors"
          value={stats.activeDoctors}
          icon={Stethoscope}
        />
        <KPICard
          title="New Content Items"
          value={stats.newContentItems}
          icon={FileText}
        />
      </div>

      {/* bottom part */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {/* Top Diseases */}
        <div className="lg:col-span-1bg-white p-4 rounded-[24px] shadow-sm">
          <TopDiseasesCard diseases={stats.topDiseases} />
        </div>

        {/*(Chart) */}
        <div className="lg:col-span-2 bg-white p-4 rounded-[24px] shadow-sm">
          <SessionStatusChart data={stats.sessionStatus} />
        </div>
        {/* roznama */}
        <div className="lg:col-span-1 bg-white/40 backdrop-blur-md rounded-[24px] border border-white/50 shadow-sm overflow-hidden flex flex-col">
          {/* الجزء الأزرق العلوي */}
          <div className="bg-[#72A6BB] p-4 text-center">
            <h3 className="text-white font-bold text-sm uppercase tracking-wider">
              {monthYear}
            </h3>
          </div>

          {/* الجزء الأبيض السفلي */}
          <div className="p-4">
            {/* صف أسماء أيام الأسبوع */}
            <div className="grid grid-cols-7 gap-1 mb-2">
              {["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"].map((day) => (
                <span
                  key={day}
                  className="text-[10px] font-bold text-gray-400 text-center"
                >
                  {day}
                </span>
              ))}
            </div>

            {/* شبكة الأيام */}
            <div className="grid grid-cols-7 gap-y-2 gap-x-1 text-center text-xs">
              {[...Array(30).keys()].map((day) => {
                const d = day + 1;
                const isToday = d === currentDay;

                return (
                  <span
                    key={d}
                    className={`h-7 w-7 flex items-center justify-center rounded-full transition-all duration-300 ${
                      isToday
                        ? "bg-[#72A6BB] text-white font-bold shadow-md scale-110"
                        : "text-gray-600 hover:bg-gray-100 cursor-pointer"
                    }`}
                  >
                    {d}
                  </span>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
