/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useState } from 'react';
import { formatDate } from '../../../common/utils/scripts';
import CalendarModal from './CalendarModal';

type Props = {
  value: Date[];
  date: Date;
  startDateLeadText: string;
  endDateLeadText: string;
  closeButtonImageUrl?: string;
  onChange: (value: Date[]) => void;
};

const DateRangeInput: FC<Props> = ({
  value,
  date,
  startDateLeadText,
  endDateLeadText,
  closeButtonImageUrl,
  onChange,
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const handleDateRangeTextClick = useCallback(
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
      onChange(dates);
    },
    [onChange]
  );

  return (
    <>
      <div
        className="text-lg leading-normal text-gray-900 underline cursor-pointer"
        onClick={handleDateRangeTextClick}
      >
        {formatDate(value[0])} - {formatDate(value[1])}
      </div>
      {isOpen && (
        <CalendarModal
          isOpen
          isRange
          initialValue={value}
          startDateLeadText={startDateLeadText}
          endDateLeadText={endDateLeadText}
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

export default DateRangeInput;
