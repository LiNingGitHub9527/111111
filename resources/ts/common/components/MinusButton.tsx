import React, { FC, memo } from 'react';
import { MinusCircleIcon } from '@heroicons/react/solid';

type Props = {
  disabled?: boolean;
  onClick: VoidFunction;
};

const MinusButton: FC<Props> = ({ disabled = false, onClick }) => {
  return (
    <button
      type="button"
      disabled={disabled}
      className="rounded-full"
      onClick={onClick}
    >
      <MinusCircleIcon
        className={['w-8 h-8', disabled && 'cursor-not-allowed']
          .filter(Boolean)
          .join(' ')}
        style={{
          color: disabled ? 'rgb(241, 241, 241)' : 'rgb(33, 133, 208)',
        }}
      />
    </button>
  );
};

export default memo(MinusButton);
