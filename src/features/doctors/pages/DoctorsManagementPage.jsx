import { useState } from "react"; // تصحيح 1: استيراد useState

import DoctorsTable from "../components/DoctorsTable";

import JoinRequestsList from "../components/JoinRequestsList";

const DoctorsManagementPage = () => {
  // تصحيح 2: إزالة السطر المكرر لتعريف المكون
  

 
  const [activeTab, setActiveTab] = useState("Doctors Directory");

  return (
    <div className="w-full">
      <div className="pt-0 p-8 space-y-6">
        {/* الهيدر والتبويبات */}
        <div className="flex justify-between items-center">
          <h1 className="text-4xl font-bold text-gray-800">
            Doctors Management
          </h1>
          {/* تصحيح 3: إضافة حدث onClick لفتح النافذة */}
          
        </div>

        {/* التبويبات */}
        <div className="flex gap-4 border-b border-gray-200 pb-2">
          {["Doctors Directory", "Join Requests", "Contract Details"].map(
            (tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)} // عند الضغط، نحدث الحالة
                className={`px-4 py-2 transition-all duration-300 ${
                  activeTab === tab
                    ? "text-[#72A6BB] border-b-2 border-[#72A6BB] font-bold" // تنسيق النشط
                    : "text-gray-500 hover:text-[#72A6BB] border-b-2 border-transparent hover:border-[#72A6BB]" // تنسيق العادي
                }`}
              >
                {tab}
              </button>
            ),
          )}
        </div>

        {/* الجدول */}
        {/* استبدل الجزء الخاص بالجدول بهذا الكود */}
        <div className="bg-white/30 backdrop-blur-md p-6 rounded-3xl border border-white/50">
          {activeTab === "Doctors Directory" && (
            <DoctorsTable  />
          )}

          {activeTab === "Join Requests" && (
            <JoinRequestsList /> // هذا المكون الذي سننشئه
          )}

          {activeTab === "Contract Details" && (
            <div className="text-gray-500 text-center py-10">
              Contract Details Section Coming Soon...
            </div>
          )}
        </div>
      </div>

      {/* تصحيح 4: استدعاء النافذة وتمرير الـ Props للتحكم بفتحها وإغلاقها */}
      
    </div>
  );
};

export default DoctorsManagementPage;
