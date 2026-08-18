import AuditFilters from "../components/AuditFilters";
import StatCard from "../components/StatCard";
import AuditLogsTable from "../components/AuditLogsTable";
import { useState } from "react";
import AuditDetailsModal from "../components/AuditDetailsModal";
import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchAuditLogs,
  fetchDashboardStats,
  fetchDoctorRequestsCount,
} from "../auditLogsSlice";

const AuditLogsPage = () => {
  const dispatch = useDispatch();

  const {
    logs,
    stats = {},
    doctorRequestsCount,
    loading,
  } = useSelector((state) => state.auditLogs);
  const handleFilterChange = (filters) => {
    const cleanFilters = Object.fromEntries(
      Object.entries(filters).filter(
        (entry) => entry[1] !== "" && entry[1] !== null,
      ),
    );
    dispatch(fetchAuditLogs(cleanFilters));
  };

  useEffect(() => {
    dispatch(fetchAuditLogs());
    dispatch(fetchDashboardStats());
    dispatch(fetchDoctorRequestsCount());
  }, [dispatch]);
  const [selectedLog, setSelectedLog] = useState(null);
  const statsDisplay = [
    { title: "Total Logs", value: stats?.count || "0" },
    { title: "Active Admins", value: "1 Admin" },

    { title: "Doctor Requests", value: doctorRequestsCount ?? "0" },
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
      <div className="grid grid-cols-1 md:grid-cols-3  gap-6">
        {statsDisplay.map((stat, index) => (
          <StatCard
            key={index}
            title={stat.title}
            value={stat.value}
            loading={loading}
          />
        ))}
      </div>
      <AuditFilters onFilterChange={handleFilterChange} />

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
