const WelcomeCard = () => {
  return (
    <div className="w-full bg-white p-4 rounded-3xl flex items-center shadow-lg gap-6 h-32">

      <div
        className="h-full w-32 flex-shrink-0 bg-contain bg-no-repeat bg-center"
        style={{
          backgroundImage: "url('/doctor-brain.png')",
        }}
      />

     
      <div className="flex-1 flex justify-between items-center pr-6">
        <div>
          <h1 className="text-3xl font-bold text-[#72A6BB]">
            Welcome, Dr. Ahmed
          </h1>
          <p className="text-[#72A6BB]/80 mt-1">
            Sunday, July 5, 2026 — You are currently available
          </p>
        </div>

        <div className="flex items-center gap-3">
          <span className="flex items-center gap-2 bg-[#72A6BB]/10 text-[#72A6BB] px-4 py-2 rounded-full border border-[#72A6BB]/20 font-medium">
            <span className="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
            Available for cases
          </span>
        </div>
      </div>
    </div>
  );
};

export default WelcomeCard;
