import { useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  insertJsonFile,
  insertPdfFile,
  clearMessages,
} from "../aiInsertionSlice";
const DiseasesPage = () => {
  const dispatch = useDispatch();
  const { loading, successMessage, error } = useSelector(
    (state) => state.aiInsertion,
  );

  const [activeTab, setActiveTab] = useState("json"); // للتبديل بين JSON و PDF
  const [selectedFile, setSelectedFile] = useState(null);

  // التعامل مع اختيار الملف
  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      setSelectedFile(e.target.files[0]);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!selectedFile) return;

    if (activeTab === "json") {
      dispatch(insertJsonFile(selectedFile));
    } else {
      dispatch(insertPdfFile(selectedFile));
    }
  };
  // const handleSubmit = (e) => {
  //   e.preventDefault();
  //   // أزلنا شرط التحقق من وجود ملف للتجربة السريعة
  //   dispatch(insertJsonFile());
  // };

  return (
    <div className="p-8 max-w-6xl mx-auto " dir="ltr">
      <h1 className="text-3xl font-bold text-gray-800 mb-6">
        INSERT DATA TO AI MODEL
      </h1>

      {/* أزرار التبديل بين نوع الملفات */}
      <div className="flex border-b border-gray-200 mb-6">
        <button
          className={`py-2 px-4 font-semibold focus:outline-none transition-colors duration-200 ${
            activeTab === "json"
              ? "border-b-2  text-[#72A6BB]"
              : "text-gray-500 hover:text-gray-700"
          }`}
          onClick={() => {
            setActiveTab("json");
            setSelectedFile(null);
            dispatch(clearMessages());
          }}
        >
          Insert JSON
        </button>
        <button
          className={`py-2 px-4 font-semibold focus:outline-none transition-colors duration-200 ${
            activeTab === "pdf"
              ? "border-b-2  text-[#72A6BB]"
              : "text-gray-500 hover:text-gray-700"
          }`}
          onClick={() => {
            setActiveTab("pdf");
            setSelectedFile(null);
            dispatch(clearMessages());
          }}
        >
          Insert PDF
        </button>
      </div>

      {/* صندوق الإدخال والرفع */}
      <div className="bg-white p-6 rounded-lg shadow-md border border-gray-100">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              {activeTab === "json" ? "select JSON file" : "select PDF file"}
            </label>
            <input
              type="file"
              accept={activeTab === "json" ? ".json" : ".pdf"}
              onChange={handleFileChange}
              className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
            />
          </div>

          {selectedFile && (
            <p className="text-sm text-gray-600">
              selected file :{" "}
              <span className="font-medium text-gray-800">
                {selectedFile.name}
              </span>
            </p>
          )}

          {/* رسائل النجاح أو الخطأ */}
          {successMessage && (
            <div className="p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
              {successMessage}
            </div>
          )}

          {error && (
            <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
              {typeof error === "object" ? JSON.stringify(error) : error}
            </div>
          )}

          <button
            type="submit"
            disabled={!selectedFile || loading}
            className="w-full py-2 px-4 rounded-md text-white font-medium transition-colors duration-200 cursor-pointer shadow-sm"
            style={{
              backgroundColor: !selectedFile || loading ? "#b0d0dc" : "#72A6BB",
            }}
          >
            {loading ? "Uploading & Processing..." : "Send to AI Model"}
          </button>
          {/* <button
            type="submit"
            disabled={loading}
            className="w-full py-2 px-4 rounded-md text-white font-medium transition-colors duration-200 cursor-pointer shadow-sm"
            style={{
              backgroundColor: loading ? "#b0d0dc" : "#72A6BB",
            }}
          >
            {loading ? "Testing Connection..." : "Test Server Connection"}
          </button> */}
        </form>
      </div>
    </div>
  );
};

export default DiseasesPage;
