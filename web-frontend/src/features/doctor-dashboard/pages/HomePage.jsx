import WelcomeCard from "../components/WelcomeCard";
import NewCaseAlert from "../components/NewCaseAlert";
import StatsRow from "../components/StatsRow";
import IncomingCases from "../components/IncomingCases";
import WeeklySchedule from "../components/WeeklySchedule";
import EarningsSummary from "../components/EarningsSummary";
const HomePage = () => {
  return (
    <div className="flex flex-col gap-6 w-full p-6">
      <WelcomeCard />
      <NewCaseAlert />
      <StatsRow />

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div className="lg:col-span-3">
          <IncomingCases />
        </div>

        <div className="lg:col-span-2 flex flex-col gap-6">
          <WeeklySchedule />
          <EarningsSummary />
        </div>
      </div>
    </div>
  );
};

export default HomePage;
