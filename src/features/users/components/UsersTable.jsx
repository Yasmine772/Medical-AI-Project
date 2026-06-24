import { useState } from "react";

const UsersTable = ({ users: initialUsers }) => {
  const [users, setUsers] = useState(initialUsers);
  const [filterCount, setFilterCount] = useState(0);

  const toggleStatus = (id) => {
    setUsers(
      users.map((user) =>
        user.id === id
          ? {
              ...user,
              status: user.status === "active" ? "non-active" : "active",
            }
          : user,
      ),
    );
  };

  const filteredUsers = users.filter(
    (user) => user.diagnosisCount >= filterCount,
  );

  return (
    <div className="p-6 bg-white/80 backdrop-blur-xl shadow-lg rounded-[24px] border border-white/20">
      {/* قسم الفلترة المطور */}
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

        {/* زر مسح الفلترة */}
        <button
          onClick={() => setFilterCount(0)}
          className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
        >
          Clear
        </button>
      </div>

      <table className="w-full text-left border-collapse">
        <thead>
          <tr className="text-gray-500 border-b border-gray-200">
            <th className="pb-4 px-4 font-medium">User Name</th>
            <th className="pb-4 px-4 font-medium">Email</th>{" "}
            <th className="pb-4 px-4 font-medium">Diagnosis Count</th>
            <th className="pb-4 px-4 font-medium">Status</th>
            <th className="pb-4 px-4 font-medium">Action</th>
          </tr>
        </thead>
        <tbody>
          {filteredUsers.map((user) => (
            <tr
              key={user.id}
              className="border-b border-gray-100 hover:bg-white/30 transition-colors"
            >
              <td className="py-4 px-4 font-semibold text-gray-800">
                {user.name}
              </td>
              {/* عرض الإيميل */}
              <td className="py-4 px-4 text-gray-600">{user.email}</td>
              <td className="py-4 px-4 text-gray-600">{user.diagnosisCount}</td>

              {/* عمود الحالة */}
              <td className="py-4 px-4">
                <span
                  className={`px-3 py-1 rounded-full text-xs font-bold w-24 inline-block text-center ${
                    user.status === "active"
                      ? "bg-green-100 text-green-700"
                      : "bg-red-100 text-red-700"
                  }`}
                >
                  {user.status === "active" ? "Active" : "Non-Active"}
                </span>
              </td>

              {/* عمود الحظر */}
              <td className="py-4 px-4">
                <button
                  onClick={() => toggleStatus(user.id)}
                  className={`relative w-12 h-6 rounded-full transition-colors duration-300 ${
                    user.status === "active" ? "bg-green-500" : "bg-red-500"
                  }`}
                >
                  <span
                    className={`absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-300 ${
                      user.status === "active"
                        ? "translate-x-6"
                        : "translate-x-0"
                    }`}
                  />
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default UsersTable;
