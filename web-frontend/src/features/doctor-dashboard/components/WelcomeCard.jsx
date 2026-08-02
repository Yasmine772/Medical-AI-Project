import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchDoctorSummary,
  updateAvailability,
} from "../doctorDashboardSlice";

const WelcomeCard = () => {
  const dispatch = useDispatch();
  const { summary } = useSelector((state) => state.doctorDashboard);

  useEffect(() => {
    dispatch(fetchDoctorSummary());
  }, [dispatch]);

  const handleToggleAvailability = () => {
    dispatch(updateAvailability());
  };

  const doctorName = summary?.full_name || "Doctor";
  const doctorPhoto = summary?.doctor_photo
    ? `http://127.0.0.1:8000/storage/${summary.doctor_photo}`
    : "/doctor-brain.png";

  const formatDate = (dateString) => {
    if (!dateString) return "";
    const [year, month, day] = dateString.split("-");
    const months = [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ];
    const monthName = months[parseInt(month, 10) - 1];
    const dateObj = new Date(year, month - 1, day);
    const dayName = dateObj.toLocaleDateString("en-US", { weekday: "long" });
    return `${dayName}, ${parseInt(day, 10)} ${monthName}, ${year}`;
  };

  const currentDate = formatDate(summary?.date);
  const isAvailable = summary?.is_available === 1;

  return (
    <div className="w-full bg-white p-4 rounded-3xl flex items-center shadow-lg gap-6 h-32">
      <div
        className="h-full w-32 flex-shrink-0 bg-cover bg-no-repeat bg-center rounded-2xl"
        style={{
          backgroundImage: `url('${doctorPhoto}')`,
        }}
      />

      <div className="flex-1 flex justify-between items-center pr-6">
        <div>
          <h1 className="text-3xl font-bold text-[#72A6BB]">
            Welcome, Dr. {doctorName}
          </h1>
          <p className="text-[#72A6BB]/80 mt-1">
            {currentDate} — You are currently{" "}
            {isAvailable ? "available" : "unavailable"}
          </p>
        </div>

        <div className="flex items-center gap-3">
     
          <button
            onClick={handleToggleAvailability}
            className={`flex items-center gap-2 px-4 py-2 rounded-full border font-medium cursor-pointer transition-all duration-300 ${
              isAvailable
                ? "bg-[#72A6BB]/10 text-[#72A6BB] border-[#72A6BB]/20 hover:bg-[#72A6BB]/20"
                : "bg-red-50 text-red-600 border-red-200 hover:bg-red-100"
            }`}
            title="Click to toggle availability"
          >
            <span
              className={`w-3 h-3 rounded-full animate-pulse ${
                isAvailable ? "bg-green-500" : "bg-red-500"
              }`}
            ></span>
            {isAvailable ? "Available for cases" : "Busy / Unavailable"}
          </button>
        </div>
      </div>
    </div>
  );
};

export default WelcomeCard;
