import { AlertCircle, Clock, Stethoscope, FileText, Check } from "lucide-react";

const CaseCard = ({
  caseId,
  patientType,
  status,
  timeInfo,
  timer,
  badges = [],
  symptoms = [],
  diseases = [],
  onReview,
  onPdf,
}) => {
  const isUrgent = status === "urgent";
  const isDone = status === "done";

  return (
    <div
      className={`bg-white/80 backdrop-blur-md border rounded-[20px] p-5 shadow-sm transition-all ${
        isUrgent
          ? "border-red-200 bg-red-50/40"
          : "border-white/40 hover:border-gray-300"
      } ${isDone ? "opacity-75" : ""}`}
    >
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <div
            className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs ${
              isUrgent
                ? "bg-red-100 text-red-700"
                : isDone
                  ? "bg-green-100 text-green-700"
                  : "bg-sky-100 text-sky-700"
            }`}
          >
            P{caseId}
          </div>
          <div>
            <div className="text-xs text-gray-400">Patient #{caseId}</div>
            <div className="text-sm font-bold text-[#2c2c2a]">
              {patientType}
            </div>
          </div>
        </div>

        <div className="flex flex-col items-end gap-1">
          {isUrgent && (
            <span className="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <AlertCircle size={12} /> Urgent
            </span>
          )}
          {status === "new" && (
            <span className="inline-flex items-center gap-1 bg-sky-100 text-sky-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              New
            </span>
          )}
          {isDone && (
            <span className="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <Check size={12} /> Completed
            </span>
          )}

          {timer ? (
            <div className="text-xs font-semibold text-red-600 flex items-center gap-1">
              <Clock size={12} /> {timer} left
            </div>
          ) : (
            <div className="text-xs text-gray-400">{timeInfo}</div>
          )}
        </div>
      </div>

      {/* Badges Info */}
      {badges.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-3">
          {badges.map((badge, idx) => (
            <span
              key={idx}
              className="text-xs text-gray-600 bg-white/60 px-2.5 py-1 rounded-lg border border-gray-100 flex items-center gap-1"
            >
              {badge}
            </span>
          ))}
        </div>
      )}

      {/* Symptoms */}
      {symptoms.length > 0 && (
        <div className="flex flex-wrap gap-1.5 mb-4">
          {symptoms.map((sym, idx) => (
            <span
              key={idx}
              className="text-xs bg-gray-100/70 border border-gray-200/50 text-gray-600 px-2.5 py-1 rounded-full"
            >
              {sym}
            </span>
          ))}
        </div>
      )}

      {/* Diseases progress bars */}
      {diseases.length > 0 && (
        <div className="space-y-2 mb-4">
          {diseases.map((d, idx) => {
            const barColor =
              d.type === "red"
                ? "bg-red-500"
                : d.type === "amber"
                  ? "bg-amber-500"
                  : "bg-green-500";
            const textColor =
              d.type === "red"
                ? "text-red-600"
                : d.type === "amber"
                  ? "text-amber-600"
                  : "text-green-600";
            return (
              <div key={idx} className="flex items-center gap-3">
                <div
                  className={`text-xs w-36 truncate ${d.bold ? "font-bold text-[#2c2c2a]" : "text-gray-400"}`}
                >
                  {d.name}
                </div>
                <div className="flex-1 h-2 bg-gray-200/60 rounded-full overflow-hidden">
                  <div
                    className={`h-full ${barColor} rounded-full`}
                    style={{ width: `${d.pct}%` }}
                  ></div>
                </div>
                <div
                  className={`text-xs w-10 text-right font-semibold ${textColor}`}
                >
                  {d.pct}%
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Actions */}
      <div className="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
        {!isDone && (
          <button
            onClick={onReview}
            disabled={isDone}
            className={`flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-xl transition-all ${
              isDone
                ? "bg-gray-200 text-gray-400 cursor-not-allowed" 
                : "bg-[#72A6BB] text-white hover:bg-[#5f92a6]"
            }`}
          >
            <Stethoscope size={14} /> Review & Send Report
          </button>
        )}
        <button
          onClick={onPdf}
          className="flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-xl bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all"
        >
          <FileText size={14} /> {isDone ? "View Sent Report" : "View PDF"}
        </button>
      </div>
    </div>
  );
};

export default CaseCard;
