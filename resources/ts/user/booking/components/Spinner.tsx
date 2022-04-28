import React, { FC } from 'react';

const Spinner: FC = () => {
  return (
    <div className="flex items-center justify-center">
      <div className="w-8 h-8 border-2 border-t-2 border-gray-200 border-solid rounded-full ease-linear spinner" />
    </div>
  );
};

export default Spinner;
