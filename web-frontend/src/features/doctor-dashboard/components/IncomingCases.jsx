import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { AlertCircle, Eye } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { fetchIncomingCases } from "../doctorDashboardSlice";

const IncomingCases = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { incomingCases } = useSelector((state) => state.doctorDashboard);

  useEffect(() => {
    dispatch(fetchIncomingCases());
  }, [dispatch]);
  
  const urgentCase =
    incomingCases && incomingCases.length > 0 ? incomingCases[0] : null;
  const otherCases =
    incomingCases && incomingCases.length > 1 ? incomingCases.slice(1) : [];

  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm w-full">
      <h2 className="text-xl font-bold text-gray-800 mb-4">Incoming Cases</h2>

      {/* Urgent Case */}
      {urgentCase ? (
        <div className="bg-red-50 border border-red-100 p-4 rounded-2xl mb-4">
          <div className="flex items-center gap-2 text-red-600 font-semibold mb-2">
            <AlertCircle size={18} />
            <span>Urgent Case</span>
          </div>
          <p className="text-gray-800 font-bold">
            {urgentCase.disease_name} — {urgentCase.probability}% Probability
          </p>
          <p className="text-gray-600 text-sm mb-4">
            Symptoms: {urgentCase.symptoms || "No symptoms provided"}.
            {urgentCase.patient?.gender &&
              ` Gender: ${urgentCase.patient.gender},`}
            {urgentCase.patient?.age && ` Age: ${urgentCase.patient.age}`}
          </p>

          <div className="flex gap-2">
            <button
              onClick={() => navigate("/Layout/cases")}
              className="flex items-center gap-2 bg-[#72A6BB] text-white px-4 py-2 rounded-xl text-sm hover:bg-[#5a8b9e] transition cursor-pointer"
            >
              <Eye size={16} /> Review Diagnosis
            </button>
          </div>
        </div>
      ) : (
        <p className="text-gray-500 text-sm mb-4">No urgent cases available.</p>
      )}

      {/* Other Cases List */}
      <div className="space-y-4">
        {otherCases.length > 0 ? (
          otherCases.map((item, index) => (
            <div
              key={index}
              className="flex items-center justify-between border-t border-gray-100 pt-3"
            >
              <div>
                <p className="font-bold text-gray-800">
                  {item.patient?.name
                    ? `Patient: ${item.patient.name}`
                    : `Patient #${item.id}`}
                </p>
                <p className="text-sm text-gray-500">
                  {item.disease_name} - Review Completed
                </p>
              </div>
              <div className="flex items-center gap-3">
                <span className="text-xs text-gray-400">Recent</span>
                <button
                  onClick={() => navigate("/Layout/cases")}
                  className="text-xs bg-gray-100 px-3 py-1 rounded-lg text-gray-600 hover:bg-gray-200 transition"
                >
                  View
                </button>
              </div>
            </div>
          ))
        ) : (
          <p className="text-gray-400 text-xs">No additional cases.</p>
        )}
      </div>
    </div>
  );
};

export default IncomingCases;
