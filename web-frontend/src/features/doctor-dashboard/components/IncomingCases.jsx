import { AlertCircle, MessageCircle, Eye } from "lucide-react";

const IncomingCases = () => {
  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm w-full">
      <h2 className="text-xl font-bold text-gray-800 mb-4">Incoming Cases</h2>

      {/* urgent case */}
      <div className="bg-red-50 border border-red-100 p-4 rounded-2xl mb-4">
        <div className="flex items-center gap-2 text-red-600 font-semibold mb-2">
          <AlertCircle size={18} />
          <span>Urgent - Expires in 45m</span>
        </div>
        <p className="text-gray-800 font-bold">Fungal Infection — 89% Probability</p>
        <p className="text-gray-600 text-sm mb-4">Symptoms: severe itching, skin rash, discolored patches. Male, 32 years old, non-smoker.</p>
        
        <div className="flex gap-2">
          <button className="flex items-center gap-2 bg-green-500 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-600 transition">
            <MessageCircle size={16} /> WhatsApp
          </button>
          <button className="flex items-center gap-2 bg-[#72A6BB] text-white px-4 py-2 rounded-xl text-sm hover:bg-[#5a8b9e] transition">
            <Eye size={16} /> Review Diagnosis
          </button>
        </div>
      </div>

     
      <div className="space-y-4">
        {[
          { id: "Patient #225", status: "Eczema - Review Completed", time: "3 hours ago" },
          { id: "Patient #223", status: "Contact Dermatitis - Review Completed", time: "Yesterday" }
        ].map((patient, index) => (
          <div key={index} className="flex items-center justify-between border-t border-gray-100 pt-3">
            <div>
              <p className="font-bold text-gray-800">{patient.id}</p>
              <p className="text-sm text-gray-500">{patient.status}</p>
            </div>
            <div className="flex items-center gap-3">
              <span className="text-xs text-gray-400">{patient.time}</span>
              <button className="text-xs bg-gray-100 px-3 py-1 rounded-lg text-gray-600">View</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default IncomingCases;