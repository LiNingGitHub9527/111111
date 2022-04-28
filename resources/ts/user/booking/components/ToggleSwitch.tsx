/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback } from 'react';

type Props = {
  text: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
};

const ToggleSwitch: FC<Props> = ({ text, checked, onChange }) => {
  const handleClick = useCallback(
    (_event?: React.MouseEvent<HTMLElement>) => {
      onChange(!checked);
    },
    [checked, onChange]
  );

  return (
    <div className="flex items-center justify-start">
      <div
        className="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in"
        onClick={handleClick}
      >
        <input
          type="checkbox"
          readOnly
          className={[
            'absolute block w-6 h-6 bg-white border-4 rounded-full appearance-none cursor-pointer',
            checked ? 'right-0 border-green-400' : 'border-gray-300',
          ].join(' ')}
          checked={checked}
        />
        <div
          className={[
            'h-6 overflow-hidden bg-gray-300 rounded-full cursor-pointer',
            checked && 'bg-green-400',
          ]
            .filter(Boolean)
            .join(' ')}
        />
      </div>
      <label className="cursor-pointer" onClick={handleClick}>
        {text}
      </label>
    </div>
  );
};

export default ToggleSwitch;
