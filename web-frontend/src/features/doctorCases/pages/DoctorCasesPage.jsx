import { useState } from "react";
import CaseCard from "../components/CaseCard";
import CasesFilter from "../components/CasesFilter";
import PdfModal from "../components/PdfModal";
import ReviewModal from "../components/ReviewModal";
import ConfirmModal from "../components/ConfirmModal";
import toast from "react-hot-toast";

const DoctorCasesPage = () => {
  const [selectedFilter, setSelectedFilter] = useState("all");
  const [selectedTimeFilter, setSelectedTimeFilter] = useState("today");

  // States modals
  const [isPdfOpen, setIsPdfOpen] = useState(false);
  const [isReviewOpen, setIsReviewOpen] = useState(false);
  const [isConfirmOpen, setIsConfirmOpen] = useState(false);

  const [currentCase, setCurrentCase] = useState(null);
  const [pdfFilename, setPdfFilename] = useState("");

  const handleOpenPdf = (filename) => {
    setPdfFilename(filename);
    setIsPdfOpen(true);
  };

  const handleOpenReview = (caseId) => {
    setCurrentCase(caseId);
    setIsPdfOpen(false);
    setIsReviewOpen(true);
  };

  const handleOpenConfirm = () => {
    setIsReviewOpen(false);
    setIsConfirmOpen(true);
  };

  const handleSendReport = () => {
    setIsConfirmOpen(false);
    toast.success(`Report successfully sent for patient #${currentCase} ✓`, {
      style: { background: "#3b6d11", color: "#fff", borderRadius: "12px" },
    });
  };

  const handleWhatsApp = (phone) => {
    toast(`Opening WhatsApp: ${phone}`, { icon: "💬" });
  };

  return (
    <div className="space-y-6 max-w-6xl mx-auto pb-10">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white/60 backdrop-blur-md p-6 rounded-[24px] border border-white/20 shadow-sm gap-4">
        <div>
          <h1 className="text-xl font-bold text-[#2c2c2a]">Incoming Cases</h1>
          <p className="text-xs text-gray-500 mt-1">Monday, July 14, 2026</p>
        </div>
        <div className="flex items-center gap-2 bg-[#eaf3de] border border-[#c0dd97] rounded-full px-4 py-2 w-fit">
          <div className="w-2 h-2 rounded-full bg-[#3b6d11] animate-pulse"></div>
          <span className="text-xs text-[#3b6d11] font-semibold">
            Available for Cases
          </span>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white/70 backdrop-blur-md border border-white/20 p-5 rounded-[20px] shadow-sm">
          <div className="text-2xl font-bold text-[#2c2c2a]">1</div>
          <div className="text-xs text-gray-500 mt-1">Urgent Now</div>
        </div>
        <div className="bg-white/70 backdrop-blur-md border border-white/20 p-5 rounded-[20px] shadow-sm">
          <div className="text-2xl font-bold text-[#2c2c2a]">2</div>
          <div className="text-xs text-gray-500 mt-1">Pending Review</div>
        </div>
        <div className="bg-white/70 backdrop-blur-md border border-white/20 p-5 rounded-[20px] shadow-sm">
          <div className="text-2xl font-bold text-[#2c2c2a]">3</div>
          <div className="text-xs text-gray-500 mt-1">Completed Today</div>
        </div>
        <div className="bg-white/70 backdrop-blur-md border border-white/20 p-5 rounded-[20px] shadow-sm">
          <div className="text-2xl font-bold text-[#2c2c2a]">140k L.S</div>
          <div className="text-xs text-gray-500 mt-1">Today's Earnings</div>
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
        <CaseCard
          caseId="227"
          patientType="Male, 32 years"
          status="urgent"
          timeInfo="15 minutes ago"
          timer="1:45"
          badges={["Smoker", "High Blood Pressure", "Blood Group A+"]}
          symptoms={[
            "Severe Itching",
            "Skin Rash",
            "Discolored Patches",
            "Skin Peeling",
          ]}
          diseases={[
            { name: "Fungal Infection", pct: 89, type: "red", bold: true },
            { name: "Eczema", pct: 61, type: "amber", bold: false },
            { name: "Contact Dermatitis", pct: 32, type: "green", bold: false },
          ]}
          onReview={() => handleOpenReview("227")}
          onPdf={() => handleOpenPdf("case_227_ai_report.pdf")}
          onWhatsapp={() => handleWhatsApp("0912345678")}
        />

        <CaseCard
          caseId="225"
          patientType="Female, 28 years"
          status="new"
          timeInfo="45 minutes ago"
          badges={["Non-Smoker", "Blood Group B+"]}
          symptoms={["Dry Skin", "Redness", "Mild Itching"]}
          diseases={[
            { name: "Eczema", pct: 78, type: "amber", bold: true },
            { name: "Psoriasis", pct: 45, type: "amber", bold: false },
          ]}
          onReview={() => handleOpenReview("225")}
          onPdf={() => handleOpenPdf("case_225_ai_report.pdf")}
          onWhatsapp={() => handleWhatsApp("0987654321")}
        />

        <CaseCard
          caseId="223"
          patientType="Male, 45 years"
          status="done"
          timeInfo="3 hours ago"
          symptoms={["Skin Inflammation", "Localized Redness"]}
          onPdf={() => handleOpenPdf("case_223_final_report.pdf")}
          onWhatsapp={() => handleWhatsApp("0911111111")}
        />
      </div>

      {/* Modals */}
      <PdfModal
        isOpen={isPdfOpen}
        onClose={() => setIsPdfOpen(false)}
        filename={pdfFilename}
        onProceed={() => handleOpenReview(currentCase || "227")}
      />

      <ReviewModal
        isOpen={isReviewOpen}
        onClose={() => setIsReviewOpen(false)}
        caseId={currentCase}
        onSubmitConfirm={handleOpenConfirm}
        onPdf={() => handleOpenPdf(`case_${currentCase}_ai_report.pdf`)}
      />

      <ConfirmModal
        isOpen={isConfirmOpen}
        onClose={() => setIsConfirmOpen(false)}
        caseId={currentCase}
        onConfirm={handleSendReport}
      />
    </div>
  );
};

export default DoctorCasesPage;
