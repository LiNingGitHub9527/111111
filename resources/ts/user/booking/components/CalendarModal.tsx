/* eslint-disable jsx-a11y/mouse-events-have-key-events */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useEffect, useMemo, useState } from 'react';
import { Calendar } from '../../../common/utils/calendar';
import { formatDate } from '../../../common/utils/scripts';
import { useVh } from '../../../common/hooks/use-vh';

const activeColor = '#60A5FA';
const highlightColor = '#DBEAFE';
const disabledColor = 'rgb(161, 162, 162)';

const CALENDAR = new Calendar();

const DAY_OF_WEEK_STRINGS = ['日', '月', '火', '水', '木', '金', '土'] as const;
type DayOfWeekString = typeof DAY_OF_WEEK_STRINGS[number];

const DATE_DISPLAY_STATUS = {
  START: 'start',
  END: 'end',
  MIDDLE: 'middle',
  SINGLE: 'single',
  SINGLE_FOCUS: 'single-focus',
  NORMAL: 'normal',
} as const;
type DateDisplayStatus =
  typeof DATE_DISPLAY_STATUS[keyof typeof DATE_DISPLAY_STATUS];

type Props = {
  isOpen?: boolean;
  isRange?: boolean;
  initialValue?: (Date | null)[];
  leadText?: string;
  startDateLeadText?: string;
  endDateLeadText?: string;
  leadTextSeparator?: string;
  currentYear: number;
  currentMonth: number;
  currentDate?: number;
  closeButtonImageUrl?: string;
  close: VoidFunction;
  onChange: (value: Date[]) => void;
};

const CalendarModal: FC<Props> = ({
  isOpen = false,
  isRange = false,
  initialValue,
  leadText,
  startDateLeadText,
  endDateLeadText,
  leadTextSeparator = ' - ',
  currentYear,
  currentMonth,
  currentDate,
  closeButtonImageUrl,
  close,
  onChange,
}) => {
  const subtitle = isRange
    ? leadText ??
      [startDateLeadText, endDateLeadText]
        .filter(Boolean)
        .join(leadTextSeparator)
    : leadText ?? startDateLeadText ?? '';

  const today = useMemo(
    (): Date | null =>
      currentDate ? new Date(currentYear, currentMonth - 1, currentDate) : null,
    [currentDate, currentMonth, currentYear]
  );
  const tomorrow = useMemo(
    (): Date | null =>
      currentDate
        ? new Date(currentYear, currentMonth - 1, currentDate + 1)
        : null,
    [currentDate, currentMonth, currentYear]
  );

  const [calendarYear, setCalendarYear] = useState<number>(currentYear);
  const [calendarMonth, setCalendarMonth] = useState<number>(currentMonth);
  const [selectedDates, setSelectedDates] = useState<(Date | null)[]>(
    isRange
      ? (initialValue?.[0] && initialValue[1] && initialValue) ?? [
          today,
          tomorrow,
        ]
      : (initialValue?.[0] && [initialValue[0], initialValue[0]]) ?? [
          today,
          today,
        ]
  );
  const [hoveredDate, setHoveredDate] = useState<Date | null>(null);

  useEffect(() => {
    if (!initialValue?.[1]) {
      return;
    }

    const initialEndDate = initialValue[1];
    const firstDateOfCurrentMonth = new Date(currentYear, currentMonth - 1, 1);
    const lastDateOfCurrentMonth = new Date(currentYear, currentMonth, 0);
    if (
      initialEndDate < firstDateOfCurrentMonth ||
      initialEndDate > lastDateOfCurrentMonth
    ) {
      setCalendarYear(initialEndDate.getFullYear());
      setCalendarMonth(initialEndDate.getMonth() + 1);
    }
  }, [currentMonth, currentYear, initialValue]);

  const validEndDate =
    selectedDates[0] &&
    selectedDates[1] &&
    selectedDates[0].getTime() < selectedDates[1].getTime()
      ? selectedDates[1]
      : null;
  const isValid = isRange
    ? !!(selectedDates[0] && validEndDate)
    : !!selectedDates[0];

  const calendarDates = useMemo(
    (): Date[][] => CALENDAR.monthDatesByWeek(calendarYear, calendarMonth),
    [calendarMonth, calendarYear]
  );

  const handlePrevMonthButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      const nextCalendarMonth = calendarMonth - 1;
      const d = new Date(calendarYear, nextCalendarMonth - 1);
      setCalendarYear(d.getFullYear());
      setCalendarMonth(d.getMonth() + 1);
    },
    [calendarMonth, calendarYear]
  );

  const handleNextMonthButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      const nextCalendarMonth = calendarMonth + 1;
      const d = new Date(calendarYear, nextCalendarMonth - 1);
      setCalendarYear(d.getFullYear());
      setCalendarMonth(d.getMonth() + 1);
    },
    [calendarMonth, calendarYear]
  );

  const handleCloseButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      close();
    },
    [close]
  );

  const createDateClickHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isPast = today && date < today;
        if (isPast) {
          return;
        }

        const firstDateOfMonth = new Date(calendarYear, calendarMonth - 1, 1);
        const lastDateOfMonth = new Date(calendarYear, calendarMonth, 0);

        if (!isRange) {
          setSelectedDates([date, date]);
          if (date > lastDateOfMonth) {
            handleNextMonthButtonClick();
          } else if (date < firstDateOfMonth) {
            handlePrevMonthButtonClick();
          }

          return;
        }

        if (!selectedDates[0] || selectedDates[1]) {
          setSelectedDates([date]);
          if (date > lastDateOfMonth) {
            handleNextMonthButtonClick();
          } else if (date < firstDateOfMonth) {
            handlePrevMonthButtonClick();
          }
        } else if (date < selectedDates[0]) {
          setSelectedDates([date, selectedDates[0]]);
        } else {
          setSelectedDates([selectedDates[0], date]);
          if (date > lastDateOfMonth) {
            handleNextMonthButtonClick();
          }
        }
      },
    [
      calendarMonth,
      calendarYear,
      handleNextMonthButtonClick,
      handlePrevMonthButtonClick,
      isRange,
      selectedDates,
      today,
    ]
  );

  const createDateMouseOverHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isPast = today && date < today;
        if (isPast) {
          return;
        }

        setHoveredDate(date);
      },
    [today]
  );

  const createDateMouseOutHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isPast = today && date < today;
        if (isPast) {
          return;
        }

        setHoveredDate(null);
      },
    [today]
  );

  const getDateDisplayStatus = useCallback(
    (date: Date): DateDisplayStatus => {
      const isPast = today && date < today;
      if (isPast) {
        return DATE_DISPLAY_STATUS.NORMAL;
      }

      if (!selectedDates[0]) {
        if (hoveredDate && date.getTime() === hoveredDate.getTime()) {
          return DATE_DISPLAY_STATUS.SINGLE_FOCUS;
        }
      } else if (!selectedDates[1]) {
        if (!hoveredDate) {
          if (date.getTime() === selectedDates[0].getTime()) {
            return DATE_DISPLAY_STATUS.START;
          } else {
            return DATE_DISPLAY_STATUS.NORMAL;
          }
        }

        const startDate =
          hoveredDate < selectedDates[0] ? hoveredDate : selectedDates[0];
        const endDate =
          hoveredDate < selectedDates[0] ? selectedDates[0] : hoveredDate;
        if (date.getTime() === startDate.getTime()) {
          return DATE_DISPLAY_STATUS.START;
        } else if (date > startDate && date.getTime() === endDate.getTime()) {
          return DATE_DISPLAY_STATUS.END;
        } else if (date > startDate && date < endDate) {
          return DATE_DISPLAY_STATUS.MIDDLE;
        }
      } else if (selectedDates[0].getTime() === selectedDates[1].getTime()) {
        if (date.getTime() === selectedDates[0].getTime()) {
          return DATE_DISPLAY_STATUS.SINGLE;
        } else if (hoveredDate && date.getTime() === hoveredDate.getTime()) {
          return DATE_DISPLAY_STATUS.SINGLE_FOCUS;
        }
      } else {
        if (date.getTime() === selectedDates[0].getTime()) {
          return DATE_DISPLAY_STATUS.START;
        } else if (date.getTime() === selectedDates[1].getTime()) {
          return DATE_DISPLAY_STATUS.END;
        } else if (date > selectedDates[0] && date < selectedDates[1]) {
          return DATE_DISPLAY_STATUS.MIDDLE;
        } else if (hoveredDate && date.getTime() === hoveredDate.getTime()) {
          return DATE_DISPLAY_STATUS.SINGLE_FOCUS;
        }
      }

      return DATE_DISPLAY_STATUS.NORMAL;
    },
    [hoveredDate, selectedDates, today]
  );

  const handleConfirmButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      if (!selectedDates[0]) {
        return;
      }

      if (!isRange) {
        onChange([selectedDates[0]]);
        handleCloseButtonClick();

        return;
      }

      if (!validEndDate) {
        return;
      }

      onChange([selectedDates[0], validEndDate]);
      handleCloseButtonClick();
    },
    [handleCloseButtonClick, isRange, onChange, selectedDates, validEndDate]
  );

  const vh = useVh();

  if (!isOpen) {
    return null;
  }

  return (
    <>
      <div
        className="static-search_panel__wrapper"
        style={{ top: `calc(56px + (${100 * vh}px - 56px) / 2)` }}
      >
        <button
          type="button"
          className="static-search_panel__close common_popup_close"
          onClick={handleCloseButtonClick}
        >
          <img src={closeButtonImageUrl} alt="close" />
        </button>
        <div className="calendar_wrap">
          <div className="calender_inner">
            <section>
              <div className="calendar_header">
                <button
                  type="button"
                  className="prev_wrap"
                  onClick={handlePrevMonthButtonClick}
                >
                  <img src="/static/common/images/prev.svg" alt="prev" />
                </button>
                <h1>
                  {calendarYear}.{calendarMonth}
                  {subtitle && <p className="inout_lead_tx">{subtitle}</p>}
                </h1>
                <button
                  type="button"
                  className="prev_wrap"
                  onClick={handleNextMonthButtonClick}
                >
                  <img src="/static/common/images/next.svg" alt="next" />
                </button>
              </div>
              <div
                className="overflow-scroll pb-2.5"
                style={{ maxHeight: `calc(${100 * vh}px - 284px)` }}
              >
                <table className="calendar_table">
                  <tbody>
                    <tr>
                      {DAY_OF_WEEK_STRINGS.map(
                        (dayOfWeekString: DayOfWeekString) => (
                          <td key={dayOfWeekString} className="dayWeek">
                            {dayOfWeekString}
                          </td>
                        )
                      )}
                    </tr>
                    {calendarDates.map((dates: Date[], index: number) => (
                      <tr key={`week-${index}`}>
                        {dates.map((date: Date) => {
                          const isOutOfMonth =
                            date.getMonth() + 1 !== calendarMonth;
                          const isPast = today && date < today;
                          const status = getDateDisplayStatus(date);

                          return (
                            <td
                              key={date.toLocaleDateString('ja-JP')}
                              className="relative date"
                              style={{
                                ...(isOutOfMonth
                                  ? {
                                      color: disabledColor,
                                    }
                                  : {}),
                                ...(isPast
                                  ? {
                                      cursor: 'default',
                                      color: disabledColor,
                                    }
                                  : {}),
                                ...(status === DATE_DISPLAY_STATUS.START
                                  ? {
                                      borderTopLeftRadius: '9999px',
                                      borderBottomLeftRadius: '9999px',
                                      backgroundColor: activeColor,
                                      color: 'white',
                                    }
                                  : {}),
                                ...(status === DATE_DISPLAY_STATUS.END
                                  ? {
                                      borderTopRightRadius: '9999px',
                                      borderBottomRightRadius: '9999px',
                                      backgroundColor: activeColor,
                                      color: 'white',
                                    }
                                  : {}),
                                ...(status === DATE_DISPLAY_STATUS.MIDDLE
                                  ? {
                                      backgroundColor: highlightColor,
                                    }
                                  : {}),
                                ...(status === DATE_DISPLAY_STATUS.SINGLE
                                  ? {
                                      borderRadius: '9999px',
                                      backgroundColor: activeColor,
                                      color: 'white',
                                    }
                                  : {}),
                                ...(status === DATE_DISPLAY_STATUS.SINGLE_FOCUS
                                  ? {
                                      borderRadius: '9999px',
                                      backgroundColor: highlightColor,
                                    }
                                  : {}),
                              }}
                              onClick={createDateClickHandler(date)}
                              onMouseOver={createDateMouseOverHandler(date)}
                              onMouseOut={createDateMouseOutHandler(date)}
                            >
                              {`0${date.getDate()}`.slice(-2)}
                              {isPast && (
                                <div className="absolute bottom-0 text-xs left-6">
                                  ×
                                </div>
                              )}
                            </td>
                          );
                        })}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </div>
        <div>
          <div className="flex border-t border-gray-200 border-solid py-2.5">
            <div className="w-1/2 px-5">
              {isRange && startDateLeadText && (
                <div className="text-sm text-gray-500">{startDateLeadText}</div>
              )}
              {!isRange && (leadText ?? startDateLeadText) && (
                <div className="text-sm text-gray-500">
                  {leadText ?? startDateLeadText}
                </div>
              )}
              <div className="text-xl font-bold leading-loose">
                {formatDate(selectedDates[0]) || '-'}
              </div>
            </div>
            {isRange && (
              <div className="w-1/2 px-5 border-l-2 border-gray-200 border-solid">
                {endDateLeadText && (
                  <div className="text-sm text-gray-500">{endDateLeadText}</div>
                )}
                <div className="text-xl font-bold leading-loose">
                  {formatDate(validEndDate) || '-'}
                </div>
              </div>
            )}
          </div>
          <div className="flex justify-between px-3 pb-3">
            <button
              type="button"
              className="px-3 text-lg leading-normal border border-black border-solid rounded py-1.5"
              style={{ width: '47%' }}
              onClick={handleCloseButtonClick}
            >
              キャンセル
            </button>
            <button
              type="button"
              disabled={!isValid}
              className={[
                'px-3 text-lg font-medium leading-normal text-white rounded py-1.5',
                !isValid && 'cursor-not-allowed',
              ]
                .filter(Boolean)
                .join(' ')}
              style={{
                width: '47%',
                backgroundColor: isValid
                  ? 'rgb(33, 133, 208)'
                  : 'rgb(241, 241, 241)',
              }}
              onClick={handleConfirmButtonClick}
            >
              適用
            </button>
          </div>
        </div>
      </div>
      <div className="static-search_panel__filter common_popup_filter" />
    </>
  );
};

export default CalendarModal;
