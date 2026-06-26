import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts";
const data = [
  { name: "Mon", respiratory: 40, oseb: 20, casg: 15, noChows: 10, cancel: 5 },
  { name: "Tue", respiratory: 80, oseb: 30, casg: 25, noChows: 15, cancel: 10 },
  { name: "Wed", respiratory: 60, oseb: 25, casg: 20, noChows: 12, cancel: 8 },
  {
    name: "Thu",
    respiratory: 130,
    oseb: 60,
    casg: 50,
    noChows: 30,
    cancel: 20,
  },
  {
    name: "Fri",
    respiratory: 100,
    oseb: 45,
    casg: 35,
    noChows: 20,
    cancel: 15,
  },
  { name: "Sat", respiratory: 80, oseb: 30, casg: 25, noChows: 15, cancel: 10 },
];

const DiagnosisChart = () => {
  return (
    <div className="!bg-white/30 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-white/50 col-span-4">
      <h3 className="text-lg font-bold mb-6">
        Diagnosis Volume{" "}
        <span className="text-gray-400 text-sm font-normal">(7-day trend)</span>
      </h3>
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data}>
            <CartesianGrid
              strokeDasharray="3 3"
              vertical={false}
              stroke="#e5e7eb"
            />
            <XAxis
              dataKey="name"
              axisLine={false}
              tickLine={false}
              tick={{ fontSize: 12 }}
            />
            <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12 }} />
            <Tooltip
              contentStyle={{
                borderRadius: "12px",
                border: "none",
                boxShadow: "0 4px 6px -1px rgb(0 0 0 / 0.1)",
              }}
            />
           
            <Legend iconType="circle" wrapperStyle={{ paddingTop: "20px" }} />

            <Line
              type="monotone"
              dataKey="respiratory"
              name="Respiratory"
              stroke="#72A6BB"
              strokeWidth={3}
              dot={false}
            />
            <Line
              type="monotone"
              dataKey="oseb"
              name="Osebam"
              stroke="#89C4B7"
              strokeWidth={3}
              dot={false}
            />
            <Line
              type="monotone"
              dataKey="casg"
              name="Casg"
              stroke="#A7B6D1"
              strokeWidth={3}
              dot={false}
            />
            <Line
              type="monotone"
              dataKey="noChows"
              name="No-chows"
              stroke="#D1B894"
              strokeWidth={3}
              dot={false}
            />
            <Line
              type="monotone"
              dataKey="cancel"
              name="Cancel"
              stroke="#E6A88B"
              strokeWidth={3}
              dot={false}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
};

export default DiagnosisChart;
