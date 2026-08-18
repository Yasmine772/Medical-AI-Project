import { useSelector, useDispatch } from "react-redux";
import { deleteDoctor } from "../doctorsSlice";
import { useOutletContext } from "react-router-dom";

const DoctorsTable = () => {
 
  const fullState = useSelector((state) => state);
  console.log("Current Redux State:", fullState);
  const doctors = useSelector((state) => state.doctors.approved || []);
  const dispatch = useDispatch();
  const { setActionModal } = useOutletContext();
  const handleDelete = (doctor) => {
    setActionModal({
      isOpen: true,
      type: "Delete", 
      onConfirm: () => {
        dispatch(deleteDoctor(doctor.id)); 
      },
    });
  };
  return (
    <div className="bg-white/30 backdrop-blur-md rounded-3xl border border-white/50 p-6 mt-6 shadow-sm">
      <h2 className="text-xl font-bold mb-6 text-gray-700">
        Doctors Directory
      </h2>
      <table className="w-full text-left border-collapse">
        <thead>
          <tr className="text-gray-400 text-sm border-b border-white/30">
            <th className="pb-4">Doctor Profile</th>
            <th className="pb-4">Specialization</th>
            <th className="pb-4">Status</th>
            <th className="pb-4">Action</th>
          </tr>
        </thead>
        <tbody className="text-gray-700">
          {doctors.length > 0 ? (
            doctors.map((doc) => (
              <tr key={doc.id} className="border-b border-white/20">
                <td className="py-4 font-semibold">{doc.name}</td>
                <td className="py-4">{doc.specialty}</td>
                <td className="py-4">
                  <span
                    className={`px-3 py-1 rounded-full text-xs ${
                      doc.status === "Active"
                        ? "bg-green-100 text-green-600"
                        : "bg-yellow-100 text-yellow-600"
                    }`}
                  >
                    {doc.status}
                  </span>
                </td>
               
                <td className="py-4">
                  <button
                    onClick={() => handleDelete(doc)}
                    className="text-red-500 font-bold hover:underline"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="4" className="py-6 text-center text-gray-500">
                No approved doctors yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
};

export default DoctorsTable;
