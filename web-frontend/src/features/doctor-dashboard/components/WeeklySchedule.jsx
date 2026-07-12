import { useState } from "react";

const WeeklySchedule = () => {
  const [isEditing, setIsEditing] = useState(false);
  // سنقوم بتفكيك الـ time إلى start و end للتحكم الأفضل
  const [schedule, setSchedule] = useState([
    { day: "Sun", start: "14", end: "09", open: true },
    { day: "Mon", start: "", end: "", open: false },
    { day: "Tue", start: "14", end: "09", open: true },
    { day: "Wed", start: "20", end: "14", open: true },
    { day: "Thu", start: "", end: "", open: false },
    { day: "Fri", start: "16", end: "10", open: true },
    { day: "Sat", start: "", end: "", open: false },
  ]);

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

  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm w-full">
      <div className="flex justify-between items-center mb-6">
        <h3 className="font-bold text-gray-800 text-lg">Weekly Schedule</h3>
        <button
          onClick={() => setIsEditing(!isEditing)}
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
                      type="number"
                      placeholder="From"
                      value={item.start}
                      onChange={(e) =>
                        handleUpdate(index, "start", e.target.value)
                      }
                      className="w-full text-center text-[9px] border rounded"
                    />
                    <input
                      type="number"
                      placeholder="To"
                      value={item.end}
                      onChange={(e) =>
                        handleUpdate(index, "end", e.target.value)
                      }
                      className="w-full text-center text-[9px] border rounded"
                    />
                  </div>
                ) : (
                  <div className="h-10 text-[8px] flex items-center justify-center text-gray-300">
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
                {item.open ? `${item.start}-${item.end}` : "Closed"}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default WeeklySchedule;
