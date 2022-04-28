import React, { FC, memo, useCallback } from 'react';
import MinusButton from './MinusButton';
import PlusButton from './PlusButton';

type Props = {
  value?: number;
  min?: number;
  max?: number;
  onChange: (value: number) => void;
};

const PlusMinusInput: FC<Props> = ({ value = 0, min, max, onChange }) => {
  const handleMinusClick = useCallback((): void => {
    onChange(value - 1);
  }, [onChange, value]);

  const handlePlusClick = useCallback((): void => {
    onChange(value + 1);
  }, [onChange, value]);

  return (
    <div className="flex items-center justify-between w-28">
      <MinusButton
        disabled={min != null && value - 1 < min}
        onClick={handleMinusClick}
      />
      <div className="text-lg">{value}</div>
      <PlusButton
        disabled={max != null && value + 1 > max}
        onClick={handlePlusClick}
      />
    </div>
  );
};

export default memo(PlusMinusInput);
