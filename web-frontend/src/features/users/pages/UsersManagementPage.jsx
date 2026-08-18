import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { fetchUsers } from "../../../features/users/usersSlice"; // تأكدي من مسار الملف
import UsersTable from "../components/UsersTable";

const UsersManagementPage = () => {
  const dispatch = useDispatch();
  const { list, loading } = useSelector((state) => state.users);

  useEffect(() => {
    dispatch(fetchUsers());
  }, [dispatch]);

  return (
    <div className="pt-0 p-8 space-y-6">
      <h1 className="text-4xl font-bold text-gray-800">User Management</h1>

      {/* إذا كان التحميل جارياً، نعرض الدائرة في منتصف الصفحة */}
      {loading ? (
        <div className="flex justify-center items-center h-[50vh]">
          <div className="w-12 h-12 border-4 border-t-transparent border-[#72A6BB] rounded-full animate-spin"></div>
        </div>
      ) : (
        /* إذا انتهى التحميل، يظهر الجدول */
        <UsersTable users={list} />
      )}
    </div>
  );
};

export default UsersManagementPage;
