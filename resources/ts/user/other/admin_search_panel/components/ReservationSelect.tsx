/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useMemo, useState } from 'react';
import { buildDayjs } from '../../../../common/utils/date';
import { Hotel } from '../../../booking/datatypes';
import { Reservation, Reservations } from '../datatypes';
import { ChevronDownIcon } from '@heroicons/react/solid';
import ReservationCalendarModal from './ReservationCalendarModal';

const getDateStrs = (blocks: Reservations): string[] =>
  Object.entries(blocks)
    .reduce((acc, [key, value]) => {
      if (value.length > 0) {
        acc.push(key);
      }

      return acc;
    }, [] as string[])
    .sort(
      (a: string, b: string) => new Date(a).getTime() - new Date(b).getTime()
    );

type Props = {
  isMultiple?: boolean;
  hotel?: Hotel;
  lpParam?: string;
  roomTypeToken: string;
  value: Reservations;
  reservationBlockListUrl: string;
  closeButtonImageUrl: string;
  onChange: (value: Reservations) => void;
};

const ReservationSelect: FC<Props> = ({
  isMultiple = false,
  hotel,
  lpParam,
  roomTypeToken,
  value,
  reservationBlockListUrl,
  closeButtonImageUrl,
  onChange,
}) => {
  const today = useMemo((): Date => {
    const now = buildDayjs();

    return now.startOf('date').toDate();
  }, []);

  const selectedReservationsCount = useMemo((): number => {
    return Object.values(value)
      .map((v: Reservation[]) => v.length)
      .reduce((acc, cur) => acc + cur, 0);
  }, [value]);

  const selectedDateStrs = useMemo((): string[] => getDateStrs(value), [value]);

  const [isOpen, setIsOpen] = useState<boolean>(false);

  const handleReservationDetailTextClick = useCallback(
    (_event?: React.MouseEvent<HTMLElement>): void => {
      setIsOpen(true);
    },
    []
  );

  const handleClose = useCallback((): void => {
    setIsOpen(false);
  }, []);

  const handleChange = useCallback(
    (v: Reservations): void => {
      onChange(v);
    },
    [onChange]
  );

  return (
    <>
      {isMultiple && (
        <div className="flex items-center justify-start mb-0.5">
          <div className="mr-2 text-base leading-normal">部屋数</div>
          <div
            className="text-base leading-normal text-gray-900 underline cursor-pointer"
            onClick={handleReservationDetailTextClick}
          >
            {selectedReservationsCount}部屋
          </div>
          <div className="mt-0.5 ml-0.5">
            <ChevronDownIcon className="w-4 h-4" />
          </div>
        </div>
      )}
      {!isMultiple && selectedReservationsCount === 0 && (
        <div className="flex items-center justify-start mb-0.5">
          <div
            className="text-base leading-normal text-gray-900 underline cursor-pointer"
            onClick={handleReservationDetailTextClick}
          >
            日時 / 人数を選択してください
          </div>
          <div className="mt-0.5 ml-0.5">
            <ChevronDownIcon className="w-4 h-4" />
          </div>
        </div>
      )}
      {selectedDateStrs.map((dateStr: string) => (
        <div key={dateStr} className="mb-0.5">
          <div className="mr-2 text-sm text-gray-500">
            {buildDayjs(dateStr).format('YYYY年M月D日(dd)')}
          </div>
          {(value[dateStr] ?? []).map((reservation: Reservation) => (
            <div
              key={reservation.block.reservation_block_token}
              className="text-base leading-normal text-gray-900 underline cursor-pointer"
              onClick={handleReservationDetailTextClick}
            >
              {reservation.block.start_time} - {reservation.block.end_time}{' '}
              (定員: {reservation.block.person_capacity}名) ¥
              {reservation.block.price.toLocaleString()} /{' '}
              {reservation.peopleNum}
              名様
            </div>
          ))}
        </div>
      ))}
      {isOpen && (
        <ReservationCalendarModal
          isOpen
          isMultiple={isMultiple}
          hotel={hotel}
          lpParam={lpParam}
          roomTypeToken={roomTypeToken}
          initialValue={value}
          currentYear={today.getFullYear()}
          currentMonth={today.getMonth() + 1}
          currentDate={today.getDate()}
          reservationBlockListUrl={reservationBlockListUrl}
          closeButtonImageUrl={closeButtonImageUrl}
          close={handleClose}
          onChange={handleChange}
        />
      )}
    </>
  );
};

export default ReservationSelect;
