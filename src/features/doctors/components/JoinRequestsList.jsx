import { useSelector, useDispatch } from "react-redux";
import { useOutletContext } from "react-router-dom";
// بما أننا داخل مجلد components، نخرج خطوة واحدة للخلف للوصول إلى doctorsSlice
import { approveDoctor, rejectDoctor } from "../doctorsSlice";
import JoinRequestCard from "./JoinRequestCard";

const JoinRequestsList = () => {
  const requests = useSelector((state) => state.doctors?.pending || []);
  const dispatch = useDispatch();
  // إذا كنتِ تستخدمين الـ OutletContext
  const { setActionModal } = useOutletContext(); 

  return (
    <div className="mt-6">
      {requests.length > 0 ? (
        requests.map((req) => (
          <JoinRequestCard
            key={req.id}
            doctor={req}
            // استدعاء المودال العام من الـ Layout
            onApprove={() => setActionModal({ 
                isOpen: true, 
                type: "Approve", 
                onConfirm: () => dispatch(approveDoctor(req.id)) 
            })}
            onReject={() => setActionModal({ 
                isOpen: true, 
                type: "Reject", 
                onConfirm: () => dispatch(rejectDoctor(req.id)) 
            })}
          />
        ))
      ) : (
        <p>No pending requests.</p>
      )}
    </div>
  );
};

export default JoinRequestsList;
