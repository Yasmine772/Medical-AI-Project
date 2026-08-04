import { useState } from "react";
import { X, Stethoscope, Send, FileText } from "lucide-react";

const ReviewModal = ({ isOpen, onClose, caseId, onSubmitConfirm, onPdf }) => {
  const [verdict, setVerdict] = useState("approve");
  const [medicalNote, setMedicalNote] = useState("");
  const [customDisease, setCustomDisease] = useState("");

  // Slider states for modification option
  const [percentages, setPercentages] = useState({
    fungal: 89,
    eczema: 61,
    dermatitis: 32,
  });

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-[24px] w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b sticky top-0 bg-white z-10">
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg border hover:bg-gray-100 text-gray-500"
          >
            <X size={16} />
          </button>
          <div className="flex items-center gap-2 font-bold text-sm text-[#2c2c2a]">
            Review Case — Patient #{caseId}{" "}
            <Stethoscope size={18} className="text-[#72A6BB]" />
          </div>
        </div>

        {/* Body Content */}
        <div className="p-6 space-y-6 text-xs text-left">
          {/* Verdict Selection */}
          <div className="space-y-3">
            <label className="font-bold text-gray-700 text-sm block border-b pb-2">
              Your Decision <span className="text-red-500">*</span>
            </label>
            <div className="space-y-2">
              {/* Option 1: Approve */}
              <label
                className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all ${verdict === "approve" ? "border-[#72A6BB] bg-sky-50/40" : "border-gray-200"}`}
              >
                <div className="flex items-start gap-3">
                  <div>
                    <div className="font-bold text-[#2c2c2a]">
                      ✅ Agree with AI Diagnosis
                    </div>
                    <div className="text-gray-400 mt-0.5">
                      Results are accurate — Original report will be sent to the
                      patient
                    </div>
                  </div>
                </div>
                <input
                  type="radio"
                  name="verdict"
                  checked={verdict === "approve"}
                  onChange={() => setVerdict("approve")}
                  className="w-4 h-4 accent-[#72A6BB]"
                />
              </label>

              {/* Option 2: Modify */}
              <label
                className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all ${verdict === "modify" ? "border-[#72A6BB] bg-sky-50/40" : "border-gray-200"}`}
              >
                <div className="flex items-start gap-3">
                  <div>
                    <div className="font-bold text-[#2c2c2a]">
                      ✏️ Modify Percentages or Order
                    </div>
                    <div className="text-gray-400 mt-0.5">
                      Diseases are correct but percentages need adjustment
                    </div>
                  </div>
                </div>
                <input
                  type="radio"
                  name="verdict"
                  checked={verdict === "modify"}
                  onChange={() => setVerdict("modify")}
                  className="w-4 h-4 accent-[#72A6BB]"
                />
              </label>

              {/* Option 3: Custom Diagnosis */}
              <label
                className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all ${verdict === "custom" ? "border-[#72A6BB] bg-sky-50/40" : "border-gray-200"}`}
              >
                <div className="flex items-start gap-3">
                  <div>
                    <div className="font-bold text-[#2c2c2a]">
                      🔄 Write a Completely Different Diagnosis
                    </div>
                    <div className="text-gray-400 mt-0.5">
                      AI result is inaccurate — I will write my own diagnosis
                    </div>
                  </div>
                </div>
                <input
                  type="radio"
                  name="verdict"
                  checked={verdict === "custom"}
                  onChange={() => setVerdict("custom")}
                  className="w-4 h-4 accent-[#72A6BB]"
                />
              </label>
            </div>
          </div>

          {/* Dynamic Section: If "approve" is selected */}
          {verdict === "approve" && (
            <div className="space-y-3">
              <h3 className="font-bold text-gray-700 text-sm border-b pb-2">
                AI Diagnosis Results
              </h3>
              <div className="space-y-2 bg-gray-50/50 p-3 rounded-xl border">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-[#2c2c2a]">
                    Fungal Infection
                  </span>
                  <span className="text-red-600 font-bold">89%</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-500">Eczema</span>
                  <span className="text-amber-600 font-bold">61%</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-500">Contact Dermatitis</span>
                  <span className="text-green-600 font-bold">32%</span>
                </div>
              </div>
            </div>
          )}

          {/* Dynamic Section: If "modify" is selected */}
          {verdict === "modify" && (
            <div className="space-y-3">
              <h3 className="font-bold text-gray-700 text-sm border-b pb-2">
                Adjust Percentages
              </h3>
              <div className="space-y-3 bg-gray-50/50 p-4 rounded-xl border">
                <div>
                  <div className="flex justify-between mb-1">
                    <span className="font-bold text-[#2c2c2a]">
                      Fungal Infection
                    </span>
                    <span className="text-red-600 font-bold">
                      {percentages.fungal}%
                    </span>
                  </div>
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={percentages.fungal}
                    onChange={(e) =>
                      setPercentages({ ...percentages, fungal: e.target.value })
                    }
                    className="w-full accent-[#72A6BB]"
                  />
                </div>

                <div>
                  <div className="flex justify-between mb-1">
                    <span className="text-gray-600">Eczema</span>
                    <span className="text-amber-600 font-bold">
                      {percentages.eczema}%
                    </span>
                  </div>
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={percentages.eczema}
                    onChange={(e) =>
                      setPercentages({ ...percentages, eczema: e.target.value })
                    }
                    className="w-full accent-[#72A6BB]"
                  />
                </div>

                <div>
                  <div className="flex justify-between mb-1">
                    <span className="text-gray-600">Contact Dermatitis</span>
                    <span className="text-green-600 font-bold">
                      {percentages.dermatitis}%
                    </span>
                  </div>
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={percentages.dermatitis}
                    onChange={(e) =>
                      setPercentages({
                        ...percentages,
                        dermatitis: e.target.value,
                      })
                    }
                    className="w-full accent-[#72A6BB]"
                  />
                </div>
              </div>
            </div>
          )}

          {/* Dynamic Section: If "custom" is selected */}
          {verdict === "custom" && (
            <div className="space-y-3">
              <h3 className="font-bold text-gray-700 text-sm border-b pb-2">
                Alternative Diagnosis
              </h3>
              <div>
                <label className="text-gray-500 block mb-1">
                  Main Disease Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={customDisease}
                  onChange={(e) => setCustomDisease(e.target.value)}
                  placeholder="e.g. Psoriasis"
                  className="w-full p-2.5 rounded-xl border border-gray-200 outline-none focus:border-[#72A6BB]"
                />
              </div>
            </div>
          )}

          {/* Medical Note Section */}
          <div className="space-y-2">
            <label className="font-bold text-gray-700 text-sm block">
              Medical Note for Patient
            </label>
            <textarea
              value={medicalNote}
              onChange={(e) => setMedicalNote(e.target.value)}
              className="w-full p-3 rounded-xl border border-gray-200 outline-none focus:border-[#72A6BB] min-h-[90px]"
              placeholder="Type your medical note here — will appear in the final report for the patient..."
            ></textarea>
            <span className="text-[11px] text-gray-400 block">
              Optional — but recommended to add value for the patient
            </span>
          </div>
        </div>

        {/* Footer Actions */}
        <div className="flex items-center justify-between p-4 border-t bg-gray-50 rounded-b-[24px] sticky bottom-0 z-10">
          <button
            onClick={onSubmitConfirm}
            className="flex items-center gap-1.5 px-5 py-2.5 font-semibold text-xs rounded-xl bg-[#2b6cb0] text-white hover:bg-[#2c5282] shadow-sm transition-all"
          >
            <Send size={14} /> Send Report to Patient
          </button>

          <div className="flex gap-2">
            <button
              onClick={onPdf}
              className="flex items-center gap-1 text-xs px-3.5 py-2.5 rounded-xl border bg-white hover:bg-gray-100 text-gray-700"
            >
              <FileText size={14} /> View PDF
            </button>
            <button
              onClick={onClose}
              className="px-4 py-2.5 text-xs rounded-xl border bg-white hover:bg-gray-100 text-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ReviewModal;
