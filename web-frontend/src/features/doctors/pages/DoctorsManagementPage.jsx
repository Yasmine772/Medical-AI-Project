import { useState } from "react";
import { useLocation } from "react-router-dom";
import DoctorsTable from "../components/DoctorsTable";
import JoinRequestsList from "../components/JoinRequestsList";
import ContractDetails from "../components/ContractDetails";

const DoctorsManagementPage = () => {
  const location = useLocation();

  const [activeTab, setActiveTab] = useState(
    location.state?.activeTab || "Doctors Directory",
  );
  const [selectedDoctorId, setSelectedDoctorId] = useState(null);

  const handleViewDetails = (doctorId) => {
    setSelectedDoctorId(doctorId);
    setActiveTab("Contract Details");
  };

  return (
    <div className="w-full">
      <div className="pt-0 p-8 space-y-6">
        {/* header */}
        <div className="flex justify-between items-center">
          <h1 className="text-4xl font-bold text-gray-800">
            Doctors Management
          </h1>
        </div>

        <div className="flex gap-4 border-b border-gray-200 pb-2">
          {["Doctors Directory", "Join Requests", "Contract Details"].map(
            (tab) => (
              <button
                key={tab}
                onClick={() => {
                  setActiveTab(tab);
                  if (tab !== "Contract Details") {
                    setSelectedDoctorId(null);
                  }
                }}
                className={`px-4 py-2 transition-all duration-300 ${
                  activeTab === tab
                    ? "text-[#72A6BB] border-b-2 border-[#72A6BB] font-bold"
                    : "text-gray-500 hover:text-[#72A6BB] border-b-2 border-transparent hover:border-[#72A6BB]"
                }`}
              >
                {tab}
              </button>
            ),
          )}
        </div>

        {/* template */}
        <div className="bg-white/30 backdrop-blur-md p-6 rounded-3xl border border-white/50">
          {activeTab === "Doctors Directory" && <DoctorsTable />}

          {activeTab === "Join Requests" && (
            <JoinRequestsList onViewDetails={handleViewDetails} />
          )}

          {activeTab === "Contract Details" &&
            (selectedDoctorId ? (
              <ContractDetails
                doctorId={selectedDoctorId}
                onBack={() => {
                  setActiveTab("Join Requests");
                  setSelectedDoctorId(null);
                }}
              />
            ) : (
              <div className="text-gray-500 text-center py-10">
                Please select a doctor request first from the "Join Requests"
                tab.
              </div>
            ))}
        </div>
      </div>
    </div>
  );
};

export default DoctorsManagementPage;
