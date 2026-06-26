import UsersTable from "../components/UsersTable";
const UsersManagementPage = () => {
  const mockUsers = [
    {
      id: 1,
      name: "Razan",
      email: "razan@test.com",
      diagnosisCount: 5,
      status: "active",
    },
    {
      id: 2,
      name: "ruba",
      email: "ruba@test.com",
      diagnosisCount: 2,
      status: "non-active",
    },
  ];

  return (
    <div className="pt-0 p-8 space-y-6">
      <h1 className="text-4xl font-bold text-gray-800">User Management</h1>
      <UsersTable users={mockUsers} />
    </div>
  );
};

export default UsersManagementPage;
