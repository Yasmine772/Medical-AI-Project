const AuditLogsTable = ({ logs, onViewDetails }) => {
  console.log("Current logs data:", logs);
  console.log("coming data", logs);
  if (!logs || logs.length === 0) {
    return <div className="p-6 text-center text-gray-500">No logs found.</div>;
  }

  return (
    <div className="overflow-x-auto bg-white/40 backdrop-blur-xl rounded-3xl border border-white/40 shadow-sm mt-6">
      <table className="w-full text-left border-collapse">
        <thead>
          <tr className="border-b border-white/30 text-[#72A6BB]">
            <th className="p-4 font-bold">Date & Time</th>
            <th className="p-4 font-bold">Category</th>
            <th className="p-4 font-bold">Event</th>
            <th className="p-4 font-bold">Actor</th>
            <th className="p-4 font-bold">Action</th>
          </tr>
        </thead>
        <tbody>
          {logs &&
            logs.map((log) => (
              <tr
                key={log.id}
                className="border-b border-white/20 hover:bg-white/20 transition-all"
              >
                <td className="p-4 text-gray-700">
                  {new Date(log.created_at).toLocaleString()}
                </td>
                <td className="p-4 text-gray-700 font-medium">
                  {log.auditable_type?.split("\\").pop()}
                </td>
                <td className="p-4">
                  <span
                    className={`px-3 py-1 rounded-full text-sm font-semibold 
  ${
    log.event === "updated"
      ? "bg-yellow-100 text-yellow-600"
      : log.event === "created"
        ? "bg-green-100 text-green-600"
        : "bg-blue-100 text-blue-600"
  }`}
                  >
                    {log.event}
                  </span>
                </td>
                <td className="p-4 text-gray-700">
                  {log.user?.full_name || "System"}
                </td>
                <td className="p-4">
                  <button
                    onClick={() => onViewDetails(log)}
                    className="px-5 py-1.5 bg-[#72A6BB]/10 text-[#72A6BB] border border-[#72A6BB]/30 rounded-xl hover:bg-[#72A6BB] hover:text-white transition-all duration-300 font-medium"
                  >
                    View
                  </button>
                </td>
              </tr>
            ))}
        </tbody>
      </table>
    </div>
  );
};

export default AuditLogsTable;
