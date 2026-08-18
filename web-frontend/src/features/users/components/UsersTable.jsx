import { useState } from "react";
import { toggleUserStatus } from "../../../features/users/usersSlice";
import { useSelector, useDispatch } from "react-redux";

const UsersTable = () => {
  const [filterCount, setFilterCount] = useState(0);
  const dispatch = useDispatch();
  const users = useSelector((state) => state.users.list);

  const handleToggle = (id) => {
    dispatch(toggleUserStatus(id));
  };

  const filteredUsers = users.filter(
    (user) => user.diagnose_num >= filterCount,
  );

  const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleString("en-US", {
      dateStyle: "short",
      timeStyle: "short",
    });
  };

  return (
    <div className="p-6 bg-white/80 backdrop-blur-xl shadow-lg rounded-[24px] border border-white/20 overflow-x-auto">
      {/* Diagnose filter */}
      <div className="mb-6 flex items-center gap-4">
        <div className="flex items-center gap-2">
          <label className="text-gray-600 font-medium">Min Diagnosis:</label>
          <input
            type="number"
            value={filterCount > 0 ? filterCount : ""}
            className="p-2 w-24 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#72A6BB]"
            placeholder="0"
            onChange={(e) => setFilterCount(Number(e.target.value))}
          />
        </div>
        <button
          onClick={() => setFilterCount(0)}
          className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
        >
          Clear
        </button>
      </div>

      {/* table*/}
      <table className="w-full text-left border-collapse min-w-[1200px]">
        <thead>
          <tr className="text-gray-500 border-b border-gray-200">
            <th className="pb-4 px-4 font-medium whitespace-nowrap">ID</th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              Full Name
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">Email</th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">Status</th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              Diagnose #
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              Email Verified
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              OTP Verified
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">OTP</th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              Expires At
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">
              Created At
            </th>
            <th className="pb-4 px-4 font-medium whitespace-nowrap">Action</th>
          </tr>
        </thead>
        <tbody>
          {filteredUsers.map((user) => (
            <tr
              key={user.id}
              className="border-b border-gray-100 hover:bg-white/30 transition-colors"
            >
              <td className="py-4 px-4 text-gray-500">{user.id}</td>
              <td className="py-4 px-4 font-semibold text-gray-800 whitespace-nowrap">
                {user.full_name}
              </td>
              <td className="py-4 px-4 text-gray-600">{user.email}</td>
              <td className="py-4 px-4">
                <span
                  className={`px-3 py-1 rounded-full text-xs font-bold ${user.status === 1 ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`}
                >
                  {user.status === 1 ? "Active" : "Non-Active"}
                </span>
              </td>
              <td className="py-4 px-4 text-gray-600 text-center">
                {user.diagnose_num}
              </td>
              <td className="py-4 px-4 text-gray-500 text-xs">
                {formatDate(user.email_verified_at)}
              </td>
              <td className="py-4 px-4 text-gray-500 text-xs">
                {formatDate(user.otp_verified_at)}
              </td>
              <td className="py-4 px-4 text-gray-600">{user.otp || "---"}</td>
              <td className="py-4 px-4 text-gray-500 text-xs">
                {formatDate(user.expires_at)}
              </td>
              <td className="py-4 px-4 text-gray-500 text-xs">
                {formatDate(user.created_at)}
              </td>
              <td className="py-4 px-4">
                <label className="flex items-center cursor-pointer">
                  <div className="relative">
                    {/* hidden Input  */}
                    <input
                      type="checkbox"
                      className="sr-only"
                      checked={user.status === 1}
                      onChange={() => handleToggle(user.id)}
                    />

                    <div
                      className={`block w-12 h-6 rounded-full transition ${user.status === 1 ? "bg-green-500" : "bg-red-500"}`}
                    ></div>

                    <div
                      className={`dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition ${user.status === 1 ? "transform translate-x-6" : ""}`}
                    ></div>
                  </div>
                </label>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default UsersTable;
