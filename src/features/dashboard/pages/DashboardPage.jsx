import KPICard from "../components/KPICard";
import { Users, Activity, Stethoscope, FileText } from "lucide-react";
import DiagnosisChart from "../components/DiagnosisChart";
import PendingApplications from "../components/PendingApplications";
import PatientTypeCard from "../components/PatientTypeCard";
import doctorImg from "../../../assets/doctor-illustration.png";

const DashboardPage = () => {
  return (
    // نستخدم h-full لضمان أن الصفحة تأخذ ارتفاع الحاوية الأب فقط

    <div className=" flex flex-col gap-4 overflow-y-auto">
      {/* القسم العلوي: الهيدر */}
      <div className="flex justify-between items-center shrink-0">
        <div>
          <h1 className="text-4xl font-bold text-gray-800 ">HOME PAGE</h1>
        </div>
        <div className="flex items-center gap-6 text-sm text-gray-600">
          <span>
            System Health:{" "}
            <span className="text-green-600 font-bold">Excellent</span>
          </span>
          <div className="text-right">
            <p className="font-bold">Dr. Sisu Roy</p>
            <p className="text-xs text-gray-400">PSTSNOTHERAPIST</p>
          </div>
        </div>
      </div>

      {/* القسم العلوي: بطاقة الترحيب + بطاقة نوع المرضى */}
      {/* قمنا بتغيير الشبكة إلى 4 أعمدة للتحكم الدقيق */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8 ">
        <div className="lg:col-span-1 flex flex-col justify-center">
          <div className="bg-white/40 backdrop-blur-md p-4 rounded-[24px] border border-white/50 shadow-sm w-56">
            {/* الشهر والسنة */}
            <div className="flex justify-between items-center mb-3">
              <h3 className="text-[#72A6BB] font-bold text-sm uppercase tracking-wider">
                June 2026
              </h3>
              <div className="flex gap-1">
                <div className="w-2 h-2 rounded-full bg-[#72A6BB]"></div>
                <div className="w-2 h-2 rounded-full bg-white"></div>
              </div>
            </div>

            {/* أيام الأسبوع */}
            <div className="grid grid-cols-7 gap-1 text-center text-[10px] text-gray-500 mb-2">
              <span>M</span>
              <span>T</span>
              <span>W</span>
              <span>T</span>
              <span>F</span>
              <span>S</span>
              <span>S</span>
            </div>

            {/* أرقام الأيام */}
            <div className="grid grid-cols-7 gap-1 text-center text-xs">
              {/* مثال لأيام الشهر */}
              {[...Array(14).keys()].map((i) => (
                <span key={i} className="text-gray-400 p-1"></span>
              ))}
              <span className="text-gray-700 p-1">15</span>
              <span className="bg-[#72A6BB] text-white rounded-lg p-1 font-bold shadow-md">
                16
              </span>
              <span className="text-gray-700 p-1">17</span>
              <span className="text-gray-700 p-1">18</span>
              <span className="text-gray-700 p-1">19</span>
              <span className="text-gray-700 p-1">20</span>
              <span className="text-gray-700 p-1">21</span>
            </div>
          </div>
        </div>
        {/* بطاقة الترحيب: نجعلها تشغل عموداً واحداً (lg:col-span-1) ونزيحها للبدء من العمود الثالث */}
        <div className="lg:col-span-2 bg-gradient-to-r from-[#72A6BB] to-white p-6 rounded-[32px] shadow-sm border border-gray-100 flex items-center relative overflow-hidden h-32">
          {/* المحتوى النصي */}
          <div className="flex-1 z-10 pl-2 space-y-1">
            <h1 className="text-2xl font-semibold text-white">
              Good morning, Doc!
            </h1>
            <p className="text-white/90 text-sm md:text-base leading-tight max-w-[200px]">
              Ready for an exciting day? You've got patients lined up!
            </p>
          </div>
          {/* تصغير حجم الصورة قليلاً لتناسب الحجم الجديد */}
          <div className="absolute right-0 top-0 bottom-0 w-40 z-0">
            <img
              src={doctorImg}
              alt="Doctor"
              className="w-full h-full object-contain"
            />
          </div>
        </div>

        {/* بطاقة نوع المرضى: نجعلها تشغل عموداً واحداً (lg:col-span-1) ليكون لها نفس الحجم */}
        {/* ستأخذ تلقائياً العمود الرابع (لأننا بدأنا الكارد الأول في الثالث) */}
        <div className="lg:col-span-1">
          <PatientTypeCard />
        </div>
      </div>

      {/* قسم البطاقات الأربعة */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 shrink-0">
        <KPICard title="Active Users" value="2,345" icon={Users} />
        <KPICard title="Daily Diagnoses" value="189" icon={Activity} />
        <KPICard title="Verified Doctors" value="78" icon={Stethoscope} />
        <KPICard title="New Content Items" value="12" icon={FileText} />
      </div>

      {/* الرسم البياني + الطلبات (تأخذ باقي المساحة المتاحة flex-1) */}
      <div className="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-0">
        <div className="lg:col-span-2 h-full min-h-0">
          <DiagnosisChart />
        </div>
        <div className="lg:col-span-1 h-full min-h-0">
          <PendingApplications />
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
