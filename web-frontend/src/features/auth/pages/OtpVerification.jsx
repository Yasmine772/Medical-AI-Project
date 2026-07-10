// import { useState } from "react";
// import OtpInput from "react-otp-input";
// import Button from "./UI/Button";
// import api from "../api/axios";

// const OtpVerification = ({ email, onBack }) => {
//   const [otp, setOtp] = useState("");
//   const [loading, setLoading] = useState(false);

//   const handleVerify = async () => {
//     setLoading(true);
//     try {
//       // إرسال البيانات كما هي في البوست مان
//       const formData = new FormData();
//       formData.append("email", email);
//       formData.append("otp", otp);

//       await api.post("/admin/verifyOtp", formData);
//       alert("تم التحقق بنجاح!");
//       // هنا يمكنك التوجيه لصفحة تعيين كلمة السر
//     } catch (error) {
//       setLoading(false);
//       // فحص ما إذا كان هناك رد من السيرفر
//       if (error.response) {
//         // السيرفر رد، لكن بكود خطأ
//         alert(error.response.data.message || "فشل التحقق");
//       } else {
//         // خطأ في الاتصال بالسيرفر نفسه
//         alert("تأكدي من اتصالك بالإنترنت ومن عمل السيرفر");
//       }
//     }
//   };

//   return (
//     <div className="flex flex-col gap-4 w-full max-w-xs mx-auto font-sans">
//       <h2 className="text-xl font-bold text-gray-800">Verify your Email</h2>
//       <p className="text-sm text-gray-500">Enter the code sent to {email}</p>

//       <OtpInput
//         value={otp}
//         onChange={setOtp}
//         numInputs={6}
//         renderInput={(props) => (
//           <input
//             {...props}
//             className="w-12 h-12 m-1 text-center border rounded-lg focus:outline-none focus:border-[#58889B]"
//           />
//         )}
//       />

//       <Button onClick={handleVerify} disabled={loading}>
//         {loading ? "Verifying..." : "Confirm"}
//       </Button>
//       <button onClick={onBack} className="text-sm text-gray-400 underline">
//         Back
//       </button>
//     </div>
//   );
// };

// export default OtpVerification;
