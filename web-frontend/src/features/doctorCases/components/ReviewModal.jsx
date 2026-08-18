import { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  X,
  Stethoscope,
  Send,
  FileText,
  User,
  Activity,
  CheckCircle2,
} from "lucide-react";
import { fetchCaseDetails, submitReview } from "../doctorCasesSlice";
import toast from "react-hot-toast";
const ReviewModal = ({
  isOpen,
  onClose,
  sessionHash,

  onPdf,
}) => {
  const dispatch = useDispatch();
  const { currentCaseDetails, detailsLoading } = useSelector(
    (state) => state.doctorCases,
  );

  const [verdict, setVerdict] = useState("approve");
  const [medicalNote, setMedicalNote] = useState("");
  const [customDisease, setCustomDisease] = useState("");
  const [customDiseaseAr, setCustomDiseaseAr] = useState("");
  const [percentages, setPercentages] = useState({});
  const [initializedHash, setInitializedHash] = useState(null);

 
  useEffect(() => {
    if (isOpen && sessionHash) {
      dispatch(fetchCaseDetails(sessionHash));
    }
  }, [isOpen, sessionHash, dispatch]);

  const aiResults = currentCaseDetails?.data?.ai_result || [];

  if (
    currentCaseDetails?.data?.session?.session_hash &&
    initializedHash !== currentCaseDetails.data.session.session_hash
  ) {
    const initialPercs = {};
    aiResults.forEach((item) => {
      initialPercs[item.disease_name] = item.probability;
    });
    setPercentages(initialPercs);
    setInitializedHash(currentCaseDetails.data.session.session_hash);
  }

  if (!isOpen) return null;

  const caseData = currentCaseDetails?.data;
  const patient = caseData?.patient;
  const symptoms = caseData?.symptoms || [];
  const tips = caseData?.tips || [];
  const handleSendReport = () => {
    if (!medicalNote.trim()) {
      toast.error("يرجى كتابة ملاحظة طبية قبل إرسال التقرير");
      return;
    }

    let payload = {};

    if (verdict === "approve") {
      payload = {
        decision: "approve",
        doctor_notes: medicalNote || null,
      };
    } else if (verdict === "modify") {
     
      const updatedAiResults = aiResults.map((item) => ({
        disease_name: item.disease_name,
        disease_name_local: item.disease_name_local,
        probability: percentages[item.disease_name] ?? item.probability,
        confidence: item.confidence,
        specialist: item.specialist,
      }));

      payload = {
        decision: "edit",
        doctor_notes: medicalNote || null,
        ai_result: updatedAiResults,
      };
    } else if (verdict === "custom") {
      if (!customDisease.trim()) {
        toast.error("يرجى كتابة اسم المرض قبل إرسال التقرير");
        return;
      }
      payload = {
        decision: "new",
        doctor_notes: medicalNote || null,
        disease_name: customDisease,
        disease_name_local: customDiseaseAr || "تشخيص مخصص", 
        disease_probability: 95,
        disease_specialist: "General Practitioner",
        disease_confidence: "High",
      };
    }

    
    dispatch(submitReview({ sessionHash, payload })).then((res) => {
      if (!res.error) {
        toast.success("Review submitted successfully!", {
          style: {
            background: "#10B981",
            color: "#fff",
            borderRadius: "16px",
            padding: "12px 20px",
            fontWeight: "500",
          },
          iconTheme: {
            primary: "#fff",
            secondary: "#10B981",
          },
        });
        onClose(); 
      } else {
       
        toast.error(
          res.payload?.message || "Failed to submit review. Please try again.",
          {
            style: {
              background: "#EF4444", 
              color: "#fff",
              borderRadius: "16px",
              padding: "12px 20px",
              fontWeight: "500",
            },
            iconTheme: {
              primary: "#fff",
              secondary: "#EF4444",
            },
          },
        );
      }
    });
  };

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
            Review Case — Patient: {caseData?.patient_name || "Loading..."}{" "}
            <Stethoscope size={18} className="text-[#72A6BB]" />
          </div>
        </div>

        {/* Body Content */}
        <div className="p-6 space-y-6 text-xs text-left">
          {detailsLoading ? (
            <div className="text-center py-10 text-gray-500">
              Loading case details...
            </div>
          ) : (
            <>
              {/* 1. Patient Summary & Medical Keys Section */}
              <div className="bg-sky-50/50 p-4 rounded-2xl border border-sky-100 space-y-3">
                <div className="flex items-center gap-2 font-bold text-gray-800 text-sm border-b pb-2">
                  <User size={16} className="text-[#72A6BB]" />
                  Patient Summary & Clinical Profile
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-gray-600">
                  <div>
                    <span className="font-semibold text-gray-700">Age:</span>{" "}
                    {patient?.age ?? "N/A"}
                  </div>
                  <div>
                    <span className="font-semibold text-gray-700">Gender:</span>{" "}
                    {patient?.gender ?? "N/A"}
                  </div>
                  <div>
                    <span className="font-semibold text-gray-700">Smoker:</span>{" "}
                    {patient?.smoker ? "Yes" : "No"}
                  </div>
                  <div>
                    <span className="font-semibold text-gray-700">
                      Diabetes:
                    </span>{" "}
                    {patient?.diabetes ? "Yes" : "No"}
                  </div>
                  <div>
                    <span className="font-semibold text-gray-700">
                      Hypertension:
                    </span>{" "}
                    {patient?.hypertension ? "Yes" : "No"}
                  </div>
                  <div>
                    <span className="font-semibold text-gray-700">
                      Activity:
                    </span>{" "}
                    {patient?.activity_level ?? "N/A"}
                  </div>
                </div>

                {/* Symptoms */}
                {symptoms.length > 0 && (
                  <div className="mt-2 pt-2 border-t border-sky-100">
                    <span className="font-semibold text-gray-700 block mb-1">
                      Reported Symptoms:
                    </span>
                    <div className="flex flex-wrap gap-1.5">
                      {symptoms.map((symptom, idx) => (
                        <span
                          key={idx}
                          className="bg-white px-2.5 py-1 rounded-lg border text-gray-700 shadow-2xs"
                        >
                          {symptom}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* 2. AI Diagnosis Results Section */}
              <div className="space-y-3">
                <h3 className="font-bold text-gray-700 text-sm border-b pb-2 flex items-center gap-2">
                  <Activity size={16} className="text-[#72A6BB]" />
                  AI Diagnosis Results
                </h3>
                <div className="space-y-2 bg-gray-50/50 p-3 rounded-xl border">
                  {aiResults.map((item, index) => (
                    <div
                      key={index}
                      className="flex items-center justify-between p-2 bg-white rounded-lg border"
                    >
                      <div>
                        <span className="font-bold text-[#2c2c2a]">
                          {item.disease_name}
                        </span>
                        <span className="text-gray-400 mx-2">
                          ({item.disease_name_local})
                        </span>
                      </div>
                      <span className="font-bold text-blue-600">
                        {item.probability}%
                      </span>
                    </div>
                  ))}
                </div>
              </div>

              {/* 3. AI Suggested Tips Section */}
              {tips.length > 0 && (
                <div className="space-y-3">
                  <h3 className="font-bold text-gray-700 text-sm border-b pb-2 flex items-center gap-2">
                    <CheckCircle2 size={16} className="text-[#72A6BB]" />
                    AI Suggested Patient Tips
                  </h3>
                  <div className="space-y-1.5 bg-emerald-50/40 p-3 rounded-xl border border-emerald-100">
                    {tips.map((tip, index) => (
                      <div
                        key={index}
                        className="flex items-start gap-2 text-gray-700"
                      >
                        <span className="text-emerald-600 font-bold">•</span>
                        <span>{tip}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

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
                    <div>
                      <div className="font-bold text-[#2c2c2a]">
                        ✅ Agree with AI Diagnosis
                      </div>
                      <div className="text-gray-400 mt-0.5">
                        Results are accurate — Original report will be sent to
                        the patient
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
                    <div>
                      <div className="font-bold text-[#2c2c2a]">
                        ✏️ Modify Percentages or Order
                      </div>
                      <div className="text-gray-400 mt-0.5">
                        Diseases are correct but percentages need adjustment
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
                    <div>
                      <div className="font-bold text-[#2c2c2a]">
                        🔄 Write a Completely Different Diagnosis
                      </div>
                      <div className="text-gray-400 mt-0.5">
                        AI result is inaccurate — I will write my own diagnosis
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

              {/* Dynamic Section: If "modify" is selected */}
              {verdict === "modify" && (
                <div className="space-y-3">
                  <h3 className="font-bold text-gray-700 text-sm border-b pb-2">
                    Adjust Percentages
                  </h3>
                  <div className="space-y-3 bg-gray-50/50 p-4 rounded-xl border">
                    {aiResults.map((item, index) => (
                      <div key={index}>
                        <div className="flex justify-between mb-1">
                          <span className="font-bold text-[#2c2c2a]">
                            {item.disease_name}
                          </span>
                          <span className="text-blue-600 font-bold">
                            {percentages[item.disease_name] ?? item.probability}
                            %
                          </span>
                        </div>
                        <input
                          type="range"
                          min="0"
                          max="100"
                          value={
                            percentages[item.disease_name] ?? item.probability
                          }
                          onChange={(e) =>
                            setPercentages({
                              ...percentages,
                              [item.disease_name]: Number(e.target.value),
                            })
                          }
                          className="w-full accent-[#72A6BB]"
                        />
                      </div>
                    ))}
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
                      placeholder="e.g. Type 1 Diabetes"
                      className="w-full p-2.5 rounded-xl border border-gray-200 outline-none focus:border-[#72A6BB]"
                    />
                  </div>
                  <div>
                    <label className="text-gray-500 block mb-1">
                      Disease Name (Arabic)
                    </label>
                    <input
                      type="text"
                      value={customDiseaseAr}
                      onChange={(e) => setCustomDiseaseAr(e.target.value)}
                      placeholder="مثال: السكري من النوع الأول"
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
                  placeholder="Type your medical note here..."
                ></textarea>
              </div>
            </>
          )}
        </div>

        {/* Footer Actions */}
        <div className="flex items-center justify-between p-4 border-t bg-gray-50 rounded-b-[24px] sticky bottom-0 z-10">
          <button
            onClick={handleSendReport}
            className="flex items-center gap-1.5 px-5 py-2.5 font-semibold text-xs rounded-xl bg-[#2b6cb0] text-white hover:bg-[#2c5282] shadow-sm transition-all"
          >
            <Send size={14} /> Send Report to Patient
          </button>

          <div className="flex gap-2">
            <button
              onClick={() => onPdf(caseData?.pdf_url)}
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
