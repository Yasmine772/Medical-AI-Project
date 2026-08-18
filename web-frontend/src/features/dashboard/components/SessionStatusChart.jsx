import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from 'recharts';

const SessionStatusChart = ({ data }) => {
  const chartData = [
    { name: 'Active', value: data.active },
    { name: 'Completed', value: data.completed },
    { name: 'Pending', value: data.pending },
  ];

  const COLORS = ['#72A6BB', '#82ca9d', '#ffc658'];

  return (
    <div className="bg-white/40 backdrop-blur-md p-6 rounded-[32px] border border-white/50 shadow-sm h-full">
      <h3 className="text-gray-700 font-bold mb-4">Diagnosis Status</h3>
      <ResponsiveContainer width="100%" height={250}>
        <PieChart>
          <Pie data={chartData} innerRadius={60} outerRadius={80} paddingAngle={5} dataKey="value">
            {chartData.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
            ))}
          </Pie>
          <Tooltip />
          <Legend />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
};

export default SessionStatusChart;