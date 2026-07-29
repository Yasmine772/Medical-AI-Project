import { useSelector, useDispatch } from "react-redux";

import { useEffect } from "react";
import { fetchDoctorRequests } from "../doctorRequestsSlice";
import JoinRequestCard from "./JoinRequestCard";

const JoinRequestsList = ({ onViewDetails }) => {
  const dispatch = useDispatch();

  
  const { requests, loading, error } = useSelector(
    (state) => state.doctorRequests,
  );

  useEffect(() => {
    dispatch(fetchDoctorRequests());
  }, [dispatch]);

  if (loading)
    return (
      <div className="text-center py-10 text-gray-500">Loading requests...</div>
    );
  if (error)
    return <div className="text-center py-10 text-red-500">Error: {error}</div>;

  return (
    <div className="mt-6">
      {requests.length > 0 ? (
        requests.map((doctor) => (
          <JoinRequestCard
            key={doctor.id}
            doctor={doctor}
            onViewDetails={onViewDetails} 
          />
        ))
      ) : (
        <p className="text-center py-6 text-gray-500">No pending requests.</p>
      )}
    </div>
  );
};

export default JoinRequestsList;
