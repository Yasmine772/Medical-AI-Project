const EarningsSummary = () => {
  return (
    <div className="bg-white/70 backdrop-blur-md border border-white/50 p-6 rounded-3xl shadow-sm">
      <h3 className="font-bold text-gray-800 mb-4">July 2036 Earnings</h3>
      <div className="space-y-3">
        <div className="flex justify-between text-sm">
          <span>12 cases × 14,000</span>
          <span className="font-bold">168,000 L.S</span>
        </div>
        <div className="flex justify-between text-sm text-red-500">
          <span>Platform Commission (30%)</span>
          <span>- 72,000 L.S</span>
        </div>
        <hr className="border-gray-200" />
        <div className="flex justify-between font-bold text-lg">
          <span>Total</span>
          <span>28,000 L.S</span>
        </div>
      </div>
    </div>
  );
};
export default EarningsSummary;