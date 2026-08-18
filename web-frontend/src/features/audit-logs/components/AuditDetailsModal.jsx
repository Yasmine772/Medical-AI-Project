import { useState } from "react";

const AuditDetailsModal = ({ log, onClose }) => {
  const [activeTab, setActiveTab] = useState("general");

  if (!log) return null;

  const renderValues = (values) => {
    if (!values || Object.keys(values).length === 0)
      return <p className="text-gray-400">No changes</p>;
    return (
      <ul className="bg-gray-50 p-4 rounded-xl border border-gray-100">
        {Object.entries(values).map(([key, val]) => (
          <li
            key={key}
            className="flex justify-between py-1 border-b border-gray-200 last:border-0"
          >
            <span className="font-semibold text-gray-600">{key}:</span>
            <span className="text-gray-800">{String(val)}</span>
          </li>
        ))}
      </ul>
    );
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
        {/* Modal Header */}
        <div className="p-6 border-b border-gray-100 flex justify-between items-center">
          <h2 className="text-2xl font-bold text-[#72A6BB]">Event Details</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-black text-2xl"
          >
            &times;
          </button>
        </div>

        {/* (Tabs) */}
        <div className="flex border-b border-gray-100">
          {["general", "changes", "technical"].map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`flex-1 py-3 text-sm font-bold capitalize transition-colors ${
                activeTab === tab
                  ? "border-b-2 border-[#72A6BB] text-[#72A6BB]"
                  : "text-gray-400"
              }`}
            >
              {tab}
            </button>
          ))}
        </div>

        {/*tabs content */}
        <div className="p-6 max-h-[60vh] overflow-y-auto">
          {activeTab === "general" && (
            <div className="space-y-4">
              <p>
                <strong>Actor:</strong> {log.user?.full_name}
              </p>
              <p>
                <strong>Category:</strong>{" "}
                {log.auditable_type?.split("\\").pop()}
              </p>
              <p>
                <strong>Event:</strong> {log.event}
              </p>
              <p>
                <strong>Date:</strong>{" "}
                {new Date(log.created_at).toLocaleString()}
              </p>
            </div>
          )}

          {activeTab === "changes" && (
            <div className="space-y-6">
              <div>
                <h3 className="font-bold mb-2">Old Values:</h3>
                {renderValues(log.old_values)}
              </div>
              <div>
                <h3 className="font-bold mb-2">New Values:</h3>
                {renderValues(log.new_values)}
              </div>
            </div>
          )}

          {activeTab === "technical" && (
            <div className="space-y-4 text-sm">
              <p>
                <strong>IP Address:</strong>{" "}
                <code className="bg-gray-100 px-2 py-1 rounded">
                  {log.ip_address}
                </code>
              </p>
              <p>
                <strong>URL:</strong>{" "}
                <span className="break-all text-blue-600 underline">
                  {log.url}
                </span>
              </p>
              <p>
                <strong>User Agent:</strong>{" "}
                <span className="text-gray-500">{log.user_agent}</span>
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AuditDetailsModal;
