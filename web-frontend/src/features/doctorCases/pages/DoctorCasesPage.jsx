import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchDoctorReviews,
  fetchCaseDetails,
  fetchReviewStats,
  fetchPdfReport,
} from "../doctorCasesSlice";
import CaseCard from "../components/CaseCard";
import CasesFilter from "../components/CasesFilter";
import ReviewModal from "../components/ReviewModal";
import toast from "react-hot-toast";

const DoctorCasesPage = () => {
  const dispatch = useDispatch();
  const { casesList, loading } = useSelector((state) => state.doctorCases);
  const { pdfLoading } = useSelector((state) => state.doctorCases);
  const [selectedFilter, setSelectedFilter] = useState("all");
  const [selectedTimeFilter, setSelectedTimeFilter] = useState("today");

  const [isReviewOpen, setIsReviewOpen] = useState(false);
  const [currentSessionHash, setCurrentSessionHash] = useState(null);

  useEffect(() => {
    dispatch(fetchReviewStats());
  }, [dispatch]);

  const stats = useSelector((state) => state.doctorCases.stats);

  useEffect(() => {
    dispatch(
      fetchDoctorReviews({
        status: selectedFilter,
        date_filter: selectedTimeFilter,
        language_code: "en",
      }),
    );
  }, [dispatch, selectedFilter, selectedTimeFilter]);

  const handleOpenReview = (sessionHash) => {
    setCurrentSessionHash(sessionHash);
    dispatch(fetchCaseDetails(sessionHash));
    setIsReviewOpen(true);
  };

  // استخراج التاريخ الحالي ديناميكياً
  const today = new Date();

  // للحصول على اسم اليوم والتاريخ بصيغة مطابقة (مثال: Monday, July 14, 2026)
  const formattedDate = today.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });

  return (
    <div className="space-y-6 max-w-6xl mx-auto pb-10" dir="ltr">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/35 backdrop-blur-md p-6 rounded-[24px] border border-white shadow-sm gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#72A6BB]">Incoming Cases</h1>
          <p className="text-xs font-semibold text-gray-500 mt-1">
            {formattedDate}
          </p>
        </div>
        <div className="flex items-center gap-2 bg-[#72A6BB]/10 border border-[#72A6BB]/30 rounded-full px-4 py-2 w-fit">
          <div className="w-2 h-2 rounded-full bg-[#72A6BB] animate-pulse"></div>
          <span className="text-xs text-[#72A6BB] font-semibold">
            Available for Cases
          </span>
        </div>
      </div>
      {/* Stats Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {/* Total Cases */}
        <div className="bg-white/30 backdrop-blur-md p-4 rounded-2xl border border-white shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs text-gray-500 font-medium">Total Cases</p>
            <h3 className="text-3xl font-bold text-[#72A6BB] mt-1">
              {stats?.total ?? 0}
            </h3>
          </div>
          <div className="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center text-[#72A6BB] font-bold">
            📊
          </div>
        </div>

        {/* Urgent Cases */}
        <div className="bg-white/30 backdrop-blur-md p-4 rounded-2xl border border-white shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs text-gray-500 font-medium">Urgent Cases</p>
            <h3 className="text-3xl font-bold text-[#72A6BB] mt-1">
              {stats?.urgent ?? 0}
            </h3>
          </div>
          <div className="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-500 font-bold">
            🚨
          </div>
        </div>

        {/* Pending Cases */}
        <div className="bg-white/30 backdrop-blur-md p-4 rounded-2xl border border-white shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs text-gray-500 font-medium">Pending Review</p>
            <h3 className="text-3xl font-bold text-[#72A6BB] mt-1">
              {stats?.pending ?? 0}
            </h3>
          </div>
          <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-500 font-bold">
            ⏳
          </div>
        </div>

        {/* Completed Cases */}
        <div className="bg-white/30 backdrop-blur-md p-4 rounded-2xl border border-white shadow-sm flex items-center justify-between">
          <div>
            <p className="text-xs text-gray-500 font-medium">Completed</p>
            <h3 className="text-3xl font-bold text-[#72A6BB] mt-1">
              {stats?.completed ?? 0}
            </h3>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-[#72A6BB] font-bold">
            ✅
          </div>
        </div>
      </div>

      {/* Filters */}
      <CasesFilter
        selectedFilter={selectedFilter}
        setSelectedFilter={setSelectedFilter}
        selectedTimeFilter={selectedTimeFilter}
        setSelectedTimeFilter={setSelectedTimeFilter}
      />

      {/* Cases List */}
      <div className="space-y-4">
        {loading ? (
          <div className="text-center py-10 text-gray-500 font-medium">
            Loading cases...
          </div>
        ) : casesList.length === 0 ? (
          <div className="text-center py-10 text-gray-400 bg-white rounded-2xl border">
            No cases found matching this filter.
          </div>
        ) : (
          casesList.map((item) => {
            const patientData = item.patient || {};
            const badges = [];
            if (patientData.smoker) badges.push("Smoker");
            if (patientData.hypertension) badges.push("High Blood Pressure");
            if (patientData.diabetes) badges.push("Diabetes");

            return (
              <CaseCard
                key={item.id}
                caseId={item.id}
                patientType={`${item.patient_name || "Patient"} (${patientData.gender || "N/A"}, ${patientData.age || "?"} yrs)`}
                status={
                  item.is_urgent
                    ? "urgent"
                    : item.status === "COMPLETED"
                      ? "done"
                      : "new"
                }
                timeInfo={
                  item.review_remaining_minutes
                    ? `${Math.round(item.review_remaining_minutes)} mins left`
                    : "Recently"
                }
                badges={badges}
                symptoms={item.symptoms || []}
                diseases={
                  item.top_diagnosis
                    ? [
                        {
                          name: item.top_diagnosis,
                          pct: 90,
                          type: "red",
                          bold: true,
                        },
                      ]
                    : []
                }
                onReview={() => {
                  if (item.status === "COMPLETED") return;
                  if (item.session_hash) {
                    handleOpenReview(item.session_hash);
                  } else {
                    toast.error("هذه الجلسة لا تحتوي على رمز مراجعة صالح");
                  }
                }}
                onPdf={() =>
                  dispatch(fetchPdfReport(item.session.session_hash))
                }
                pdfLoading={pdfLoading}
              />
            );
          })
        )}
      </div>

      {/* Review Modal */}
      <ReviewModal
        isOpen={isReviewOpen}
        onClose={() => setIsReviewOpen(false)}
        sessionHash={currentSessionHash}
        onPdf={() => dispatch(fetchPdfReport(currentSessionHash))}
      />
    </div>
  );
};

export default DoctorCasesPage;
