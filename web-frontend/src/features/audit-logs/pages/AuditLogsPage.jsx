import AuditFilters from "../components/AuditFilters";
import StatCard from "../components/StatCard";
import AuditLogsTable from "../components/AuditLogsTable";
import { useState } from "react";
import AuditDetailsModal from "../components/AuditDetailsModal";
import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchAuditLogs } from "../auditLogsSlice";

const AuditLogsPage = () => {
  const dispatch = useDispatch();
  const { logs, loading } = useSelector((state) => state.auditLogs);

  useEffect(() => {
    dispatch(fetchAuditLogs());
  }, [dispatch]);
  const [selectedLog, setSelectedLog] = useState(null);
  const stats = [
    { title: "Total Logs", value: "134" },
    { title: "Data Changes", value: "38" },
    { title: "Doctor Requests", value: "29" },
    { title: "Sent Notifications", value: "57" },
  ];
  if (loading) {
    return (
      <div className="flex justify-center items-center h-screen text-[#72A6BB] text-xl font-bold">
        Loading Logs...
      </div>
    );
  }
  return (
    <div className="flex flex-col gap-6 p-2">
      <div className="flex justify-between items-center">
        <h1 className="text-5xl font-bold text-black">Audit Logs</h1>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, index) => (
          <StatCard key={index} title={stat.title} value={stat.value} />
        ))}
      </div>
      <AuditFilters />

      <AuditLogsTable
        logs={logs} 
        onViewDetails={(log) => setSelectedLog(log)}
      />
      <AuditDetailsModal
        log={selectedLog}
        onClose={() => setSelectedLog(null)}
      />
    </div>
  );
};

export default AuditLogsPage;
