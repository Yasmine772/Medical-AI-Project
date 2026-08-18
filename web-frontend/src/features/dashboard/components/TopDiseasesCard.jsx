const TopDiseasesCard = ({ diseases }) => {
  const maxDiagnoses = Math.max(...diseases.map((d) => d.total_diagnoses), 1);

  return (
    <div className="bg-white/40 backdrop-blur-md p-6 rounded-[32px] border border-white/50 shadow-sm h-full">
      <h3 className="text-gray-700 font-bold mb-6">Top Diseases</h3>
      <div className="space-y-6">
        {diseases?.map((item, index) => {
          const percentage = (item.total_diagnoses / maxDiagnoses) * 100;
          return (
            <div key={index} className="space-y-2">
              <div className="flex justify-between items-center text-sm">
                <div className="flex items-center gap-3">
                  <span className="font-bold text-gray-400">0{index + 1}</span>
                  <span className="text-gray-700 font-medium">
                    {item.disease_name}
                  </span>
                </div>
                <span className="text-gray-900 font-bold">
                  {item.total_diagnoses}
                </span>
              </div>

              {/*(Progress Bar) */}
              <div className="w-full bg-gray-200 rounded-full h-1.5">
                <div
                  className="bg-[#72A6BB] h-1.5 rounded-full transition-all duration-500"
                  style={{ width: `${percentage}%` }}
                ></div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default TopDiseasesCard;
