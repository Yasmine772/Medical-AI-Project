import { useEffect, useState } from "react";
import api from "../../../api/axios";

const ContractDetails = ({ doctorId, onBack }) => {
  const [doctorDetails, setDoctorDetails] = useState(null);
  const [loading, setLoading] = useState(true);

  
  const [successModal, setSuccessModal] = useState({
    isOpen: false,
    message: "",
  });
  
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectionReason, setRejectionReason] = useState("");

  useEffect(() => {
    const fetchDetails = async () => {
      try {
        const response = await api.get(`/admin/doctor-requests/${doctorId}`);
        setDoctorDetails(response.data.data);
      } catch (error) {
        console.error("Failed to fetch doctor details", error);
      } finally {
        setLoading(false);
      }
    };

    if (doctorId) {
      fetchDetails();
    }
  }, [doctorId]);
  const handleApprove = async () => {
    try {
      const response = await api.patch(
        `/admin/doctor-requests/approve/${doctorId}`,
      );
      
      setSuccessModal({
        isOpen: true,
        message:
          response.data.message || "Doctor request approved successfully!",
      });
    } catch (error) {
      console.error("Failed to approve request", error);
    }
  };
 
  const handleReject = async () => {
    if (!rejectionReason.trim()) return; // write the reason
    try {
      const response = await api.patch(
        `/admin/doctor-requests/reject/${doctorId}`,
        null,
        {
          params: { rejection_reason: rejectionReason },
        },
      );
      console.log("Current Token:", localStorage.getItem("token"));
      setRejectModalOpen(false);
      setSuccessModal({
        isOpen: true,
        message:
          response.data.message || "Doctor request rejected successfully!",
      });
    } catch (error) {
      console.error("Failed to reject request", error);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-12 text-gray-500">Loading details...</div>
    );
  }

  if (!doctorDetails) {
    return (
      <div className="text-center py-12 text-red-500">No details found.</div>
    );
  }

  return (
    <div className="p-8 bg-white rounded-2xl shadow-sm border border-gray-100 max-w-4xl mx-auto">
     
      <button
        onClick={onBack}
        className="mb-6 text-sm text-gray-500 hover:text-gray-800 transition flex items-center gap-1 font-medium"
      >
        ← Back to Requests
      </button>

      <h2 className="text-2xl font-bold text-gray-800 mb-6">
        Doctor Application Details
      </h2>

      
      <div className="grid grid-cols-2 gap-6 mb-6">
        <div>
          <p className="text-sm text-gray-400">Full Name</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.full_name}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Email</p>
          <p className="font-semibold text-gray-800">{doctorDetails.email}</p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Phone</p>
          <p className="font-semibold text-gray-800">{doctorDetails.phone}</p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Specialization</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.specialization}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Years of Experience</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.years_of_experience} years
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Clinic Phone</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.clinic_phone || "N/A"}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-400">License Number</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.license_number}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Biography</p>
          <p className="font-semibold text-gray-800">
            {doctorDetails.biography || "N/A"}
          </p>
        </div>
      </div>

     
      <div className="flex gap-4 mb-8 pt-4 border-t border-gray-100">
        {doctorDetails.cv_file && (
          <a
            href={`http://127.0.0.1:8000/storage/${doctorDetails.cv_file}`}
            target="_blank"
            rel="noopener noreferrer"
            className="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-200 transition"
          >
            📄 View CV
          </a>
        )}
        {doctorDetails.license_file && (
          <a
            href={`http://127.0.0.1:8000/storage/${doctorDetails.license_file}`}
            target="_blank"
            rel="noopener noreferrer"
            className="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-200 transition"
          >
            📜 View License File
          </a>
        )}
      </div>

     
      <div className="flex items-center justify-end gap-4 pt-6 border-t border-gray-100">
        <button
          onClick={() => setRejectModalOpen(true)}
          className="px-8 py-2.5 bg-red-50 text-red-600 rounded-xl font-semibold hover:bg-red-100 transition"
        >
          Reject
        </button>
        <button
          onClick={handleApprove}
          className="px-8 py-2.5 bg-[#72A6BB] text-white rounded-xl font-semibold hover:bg-[#5f8d9f] transition shadow-sm"
        >
          Approve
        </button>
      </div>

    
      {rejectModalOpen && (
        <div className="fixed inset-0 w-screen h-screen flex items-center justify-center z-[9999] pointer-events-none">
          <div className="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full mx-4 border border-gray-100 pointer-events-auto">
            <h3 className="text-lg font-bold text-gray-800 mb-2">
              Rejection Reason
            </h3>
            <p className="text-gray-500 text-sm mb-4">
              Please enter the reason for rejecting this request. It will be
              sent via email to the doctor.
            </p>
            <textarea
              value={rejectionReason}
              onChange={(e) => setRejectionReason(e.target.value)}
              placeholder="Type reason here..."
              className="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:border-[#72A6BB] text-sm mb-4 h-28 resize-none"
            />
            <div className="flex justify-end gap-3">
              <button
                onClick={() => setRejectModalOpen(false)}
                className="px-5 py-2 bg-gray-100 text-gray-600 rounded-xl font-semibold hover:bg-gray-200 transition text-sm"
              >
                Cancel
              </button>
              <button
                onClick={handleReject}
                className="px-5 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition text-sm shadow-sm"
              >
                Submit Rejection
              </button>
            </div>
          </div>
        </div>
      )}

      {successModal.isOpen && (
        <div className="fixed inset-0  flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-2xl shadow-xl max-w-sm w-full mx-4 text-center border border-gray-100 animate-in fade-in zoom-in duration-200">
            <div className="w-12 h-12 bg-[#72A6BB]/10 text-[#72A6BB] rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">
              ✓
            </div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Success</h3>
            <p className="text-gray-600 text-sm mb-6">{successModal.message}</p>
            <button
              onClick={() => {
                setSuccessModal({ isOpen: false, message: "" });
                onBack(); 
              }}
              className="w-full py-2.5 bg-[#72A6BB] text-white rounded-xl font-semibold hover:bg-[#5f8d9f] transition shadow-sm"
            >
             ok
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ContractDetails; 
