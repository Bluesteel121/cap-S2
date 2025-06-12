import React from 'react';

const AccountSelection = () => {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
      <h1 className="text-3xl font-bold mb-8">Select Account Type</h1>
      <div className="flex space-x-4">
        <button className="px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50">
          Admin
        </button>
        <button className="px-6 py-3 text-lg font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-opacity-50">
          User
        </button>
      </div>
    </div>
  );
};

export default AccountSelection;