import { Bell } from "lucide-react";

const NewCaseAlert = () => {
  return (
    <div className="w-full bg-[#FDF6B2]/40 backdrop-blur-md border border-[#FDE047]/50 p-4 rounded-3xl flex items-center gap-4 shadow-sm">
      <div className="bg-[#FDE047]/50 p-2 rounded-full">
        <Bell className="text-yellow-800" size={20} />
      </div>

      <div className="flex-1">
        <p className="text-yellow-900 font-medium">
          New case arrived 15 minutes ago — 45 minutes left for automatic
          reassignment.
        </p>
      </div>

      <button className="bg-white/60 hover:bg-white text-yellow-900 px-6 py-2 rounded-xl font-medium transition-colors border border-yellow-200">
        Review Now
      </button>
    </div>
  );
};

export default NewCaseAlert;
