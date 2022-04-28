/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useState } from 'react';
import { formatDate } from '../../../../common/utils/scripts';
import CalendarModal from '../../components/CalendarModal';

type Props = {
  value: Date;
  date: Date;
  leadText: string;
  closeButtonImageUrl?: string;
  onChange: (value: Date) => void;
};

const DateInput: FC<Props> = ({
  value,
  date,
  leadText,
  closeButtonImageUrl,
  onChange,
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const handleDateTextClick = useCallback(
    (_event?: React.MouseEvent<HTMLElement>) => {
      setIsOpen(true);
    },
    []
  );

  const handleClose = useCallback((): void => {
    setIsOpen(false);
  }, []);

  const handleChange = useCallback(
    (dates: Date[]): void => {
      onChange(dates[0]);
    },
    [onChange]
  );

  return (
    <>
      <div
        className="text-lg leading-normal text-gray-900 underline cursor-pointer"
        onClick={handleDateTextClick}
      >
        {formatDate(value)}
      </div>
      {isOpen && (
        <CalendarModal
          isOpen
          initialValue={[value]}
          leadText={leadText}
          currentYear={date.getFullYear()}
          currentMonth={date.getMonth() + 1}
          currentDate={date.getDate()}
          closeButtonImageUrl={closeButtonImageUrl}
          close={handleClose}
          onChange={handleChange}
        />
      )}
    </>
  );
};

export default DateInput;
