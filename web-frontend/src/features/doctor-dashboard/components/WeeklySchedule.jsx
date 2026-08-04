import { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchDoctorSchedules,
  updateDoctorScheduleItem,
} from "../doctorDashboardSlice";

const WeeklySchedule = () => {
  const dispatch = useDispatch();
  const apiSchedules = useSelector((state) => state.doctorDashboard.schedules);

  const [isEditing, setIsEditing] = useState(false);

  const baseDays = [
    { full: "Sunday", short: "Sun" },
    { full: "Monday", short: "Mon" },
    { full: "Tuesday", short: "Tue" },
    { full: "Wednesday", short: "Wed" },
    { full: "Thursday", short: "Thu" },
    { full: "Friday", short: "Fri" },
    { full: "Saturday", short: "Sat" },
  ];

  const formatSchedules = (schedules) => {
    return baseDays.map((bDay) => {
      const found = schedules?.find(
        (item) =>
          item.day_of_week &&
          item.day_of_week.toLowerCase().startsWith(bDay.short.toLowerCase()),
      );

      return {
        id: found ? found.id : null, // الاحتفاظ بالـ ID الخاص بالجدول إن وجد
        day: bDay.short,
        fullDay: bDay.full,
        start: found && found.start_time ? found.start_time.slice(0, 5) : "",
        end: found && found.end_time ? found.end_time.slice(0, 5) : "",
        open: found ? !found.is_closed : false, // التعامل مع is_closed كـ boolean
      };
    });
  };

  const [schedule, setSchedule] = useState(() => formatSchedules(apiSchedules));

  useEffect(() => {
    dispatch(fetchDoctorSchedules());
  }, [dispatch]);

  const [prevApiSchedules, setPrevApiSchedules] = useState(apiSchedules);
  if (apiSchedules !== prevApiSchedules) {
    setPrevApiSchedules(apiSchedules);
    setSchedule(formatSchedules(apiSchedules));
  }

  const handleUpdate = (index, field, value) => {
    const newSchedule = [...schedule];
    newSchedule[index][field] = value;
    setSchedule(newSchedule);
  };

  const toggleDay = (index) => {
    const newSchedule = [...schedule];
    newSchedule[index].open = !newSchedule[index].open;
    setSchedule(newSchedule);
  };

  const handleSave = () => {
    if (isEditing) {
      schedule.forEach((item) => {
        if (item.id) {
          let scheduleData;

          if (!item.open) {
            scheduleData = {
              day_of_week: item.day,
              start_time: "00:00:00",
              end_time: "00:00:01",
              is_closed: true,
            };
          } else {
            const startTime = item.start
              ? item.start.length === 5
                ? `${item.start}:00`
                : item.start
              : "00:00:00";
            const endTime = item.end
              ? item.end.length === 5
                ? `${item.end}:00`
                : item.end
              : "00:00:00";

            scheduleData = {
              day_of_week: item.day,
              start_time: startTime,
              end_time: endTime,
              is_closed: false,
            };
          }

          dispatch(updateDoctorScheduleItem({ id: item.id, scheduleData }));
        }
      });
    }
    setIsEditing(!isEditing);
  };
  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm w-full">
      <div className="flex justify-between items-center mb-6">
        <h3 className="font-bold text-gray-800 text-lg">Weekly Schedule</h3>
        <button
          onClick={handleSave}
          className={`text-xs px-4 py-1.5 rounded-lg transition-colors ${isEditing ? "bg-[#72A6BB] text-white" : "bg-[#D17D87] text-white"}`}
        >
          {isEditing ? "Save Changes" : "Edit"}
        </button>
      </div>

      <div className="grid grid-cols-7 gap-1">
        {schedule.map((item, index) => (
          <div key={index} className="flex flex-col items-center gap-1">
            <span className="text-[9px] text-gray-500 font-bold">
              {item.day}
            </span>

            {isEditing ? (
              <div className="flex flex-col gap-1 w-full">
                {item.open ? (
                  <div className="flex flex-col gap-0.5">
                    <input
                      type="text"
                      placeholder="09:00"
                      value={item.start}
                      onChange={(e) =>
                        handleUpdate(index, "start", e.target.value)
                      }
                      className="w-full text-center text-[9px] border rounded py-1"
                    />
                    <input
                      type="text"
                      placeholder="17:00"
                      value={item.end}
                      onChange={(e) =>
                        handleUpdate(index, "end", e.target.value)
                      }
                      className="w-full text-center text-[9px] border rounded py-1"
                    />
                  </div>
                ) : (
                  <div className="h-12 text-[8px] flex items-center justify-center text-gray-300">
                    Closed
                  </div>
                )}

                <button
                  onClick={() => toggleDay(index)}
                  className={`text-[8px] p-1 rounded ${item.open ? "bg-red-50 text-red-500" : "bg-[#72A6BB] text-white"}`}
                >
                  {item.open ? "Close" : "Open"}
                </button>
              </div>
            ) : (
              <div
                className={`rounded-xl text-[9px] font-bold w-full aspect-square flex flex-col items-center justify-center p-0.5 ${item.open ? "bg-[#72A6BB] text-white" : "bg-gray-100 text-gray-400"}`}
              >
                {item.open ? (
                  <>
                    <span>{item.start}</span>
                    <span className="text-[7px] opacity-75">to</span>
                    <span>{item.end}</span>
                  </>
                ) : (
                  "Closed"
                )}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default WeeklySchedule;
