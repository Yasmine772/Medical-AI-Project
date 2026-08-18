
import Navbar from '../components/Navbar';
import HeroSection from '../components/HeroSection';
import HowItWorks from '../components/HowItWorks';
import FeaturesSection from '../components/FeaturesSection';
import DoctorJoinSection from '../components/DoctorJoinSection';
import Footer from '../components/Footer';

export default function JoiningRequestsPage() {
  return (
    <div className="min-h-screen bg-white text-black font-['Segoe_UI',Tahoma,sans-serif] rtl" dir="rtl">
      <Navbar />
      <HeroSection />
      <hr className="border-t border-gray-200" />
      <HowItWorks />
      <hr className="border-t border-gray-200" />
      <FeaturesSection />
      <div id="doctor-join">
        <DoctorJoinSection />
      </div>
      <Footer />
    </div>
  );
}