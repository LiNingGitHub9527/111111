/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/mouse-events-have-key-events */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, {
  FC,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useVh } from '../../../../common/hooks/use-vh';
import { buildDayjs } from '../../../../common/utils/date';
import { Calendar } from '../../../../common/utils/calendar';
import api from '../../../../common/utils/api';
import { Hotel } from '../../../booking/datatypes';
import {
  ReservationBlock,
  ReservationBlocks,
  ReservationBlocksQueryParams,
  ReservationBlocksResponseData,
} from '../../datatypes';
import {
  PeopleNumLists,
  Reservation,
  Reservations,
  RoomNums,
} from '../datatypes';
import { DEFAULT_ERROR_MESSAGE } from '../../../booking/search_panel/constants';
import { CheckIcon } from '@heroicons/react/solid';
import PlusMinusInput from '../../../../common/components/PlusMinusInput';
import Spinner from '../../../booking/components/Spinner';

const activeColor = '#60A5FA';
const highlightedColor = '#DBEAFE';
const disabledColor = 'rgb(161, 162, 162)';

const CALENDAR = new Calendar();

const DAY_OF_WEEK_STRINGS = ['日', '月', '火', '水', '木', '金', '土'] as const;
type DayOfWeekString = typeof DAY_OF_WEEK_STRINGS[number];

const DATE_DISPLAY_STATUS = {
  AVAILABLE: 'available',
  HIGHLIGHTED: 'highlighted',
  ACTIVE: 'active',
  NORMAL: 'normal',
} as const;
type DateDisplayStatus =
  typeof DATE_DISPLAY_STATUS[keyof typeof DATE_DISPLAY_STATUS];

const getDates = (obj: ReservationBlocks | Reservations): Date[] =>
  Object.entries(obj)
    .reduce((acc, [key, value]) => {
      if (value.length > 0) {
        const date = buildDayjs(key).toDate();
        acc.push(date);
      }

      return acc;
    }, [] as Date[])
    .sort((a: Date, b: Date) => a.getTime() - b.getTime());

type Props = {
  isOpen?: boolean;
  isMultiple?: boolean;
  hotel?: Hotel;
  lpParam?: string;
  roomTypeToken: string;
  initialValue?: Reservations;
  currentYear: number;
  currentMonth: number;
  currentDate?: number;
  reservationBlockListUrl: string;
  closeButtonImageUrl: string;
  close: VoidFunction;
  onChange: (value: Reservations) => void;
};

const ReservationCalendarModal: FC<Props> = ({
  isOpen = false,
  isMultiple = false,
  hotel,
  lpParam,
  roomTypeToken,
  initialValue = {},
  currentYear,
  currentMonth,
  currentDate,
  reservationBlockListUrl,
  closeButtonImageUrl,
  close,
  onChange,
}) => {
  const [calendarYear, setCalendarYear] = useState<number>(currentYear);
  const [calendarMonth, setCalendarMonth] = useState<number>(currentMonth);

  const calendarDates = useMemo(
    (): Date[][] => CALENDAR.monthDatesByWeek(calendarYear, calendarMonth),
    [calendarMonth, calendarYear]
  );

  const today = useMemo(
    (): Date | null =>
      currentDate ? new Date(currentYear, currentMonth - 1, currentDate) : null,
    [currentDate, currentMonth, currentYear]
  );

  const currentMonthFirstDateNotPassed = useMemo((): Date => {
    const firstDate = calendarDates[0][0];
    if (!today) {
      return firstDate;
    }

    return today.getTime() < firstDate.getTime() ? firstDate : today;
  }, [calendarDates, today]);

  const currentMonthLastDate = useMemo((): Date => {
    const lastWeekDates = calendarDates[calendarDates.length - 1];

    return lastWeekDates[lastWeekDates.length - 1];
  }, [calendarDates]);

  const isCalendarOutdated = useMemo((): boolean => {
    if (!today) {
      return false;
    }

    if (today.getTime() > currentMonthLastDate.getTime()) {
      return true;
    }

    return false;
  }, [currentMonthLastDate, today]);

  const [reservationBlocks, setReservationBlocks] = useState<ReservationBlocks>(
    {}
  );
  // console.log('reservation blocks');
  // console.log(reservationBlocks);
  const reservationBlocksRef = useRef<ReservationBlocks>({});
  const [isReservationBlocksFetching, setIsReservationBlocksFetching] =
    useState<boolean>(false);

  const fetchReservationBlocks = useCallback(async (): Promise<void> => {
    if (!hotel?.id) {
      return;
    }

    try {
      setIsReservationBlocksFetching(true);
      const startDate = buildDayjs(currentMonthFirstDateNotPassed).format(
        'YYYY-MM-DD'
      );
      const endDate = buildDayjs(currentMonthLastDate).format('YYYY-MM-DD');
      const params: ReservationBlocksQueryParams = {
        hotel_id: hotel.id,
        room_type_token: roomTypeToken,
        start_date: startDate,
        end_date: endDate,
        is_available: true,
        url_param: '',
      };
      // console.log('reservation block list query params:');
      // console.log(params);
      const res = (await api.get(
        reservationBlockListUrl,
        params
      )) as ReservationBlocksResponseData;
      // console.log('reservation block list query response:');
      // console.log(res);
      if (res.status === 'OK') {
        const nextReservationBlocks = {
          ...reservationBlocksRef.current,
          ...res.data.reservation_blocks,
        };
        reservationBlocksRef.current = nextReservationBlocks;
        setReservationBlocks(nextReservationBlocks);
      }
    } catch (_error) {
      alert(DEFAULT_ERROR_MESSAGE);
    } finally {
      setIsReservationBlocksFetching(false);
    }
  }, [
    currentMonthFirstDateNotPassed,
    currentMonthLastDate,
    hotel?.id,
    reservationBlockListUrl,
    roomTypeToken,
  ]);

  useEffect(() => {
    if (
      !hotel?.id ||
      !lpParam ||
      !roomTypeToken ||
      !reservationBlockListUrl ||
      isCalendarOutdated
    ) {
      return;
    }

    fetchReservationBlocks();
  }, [
    fetchReservationBlocks,
    hotel?.id,
    isCalendarOutdated,
    lpParam,
    reservationBlockListUrl,
    roomTypeToken,
  ]);

  const availableDates = useMemo(
    (): Date[] => getDates(reservationBlocks),
    [reservationBlocks]
  );
  // console.log('available dates:');
  // console.log(availableDates);

  const getIsAvailable = useCallback(
    (date: Date): boolean =>
      availableDates.some((d: Date) => d.getTime() === date.getTime()),
    [availableDates]
  );

  const initialActiveDate = useMemo((): Date | null => {
    let initialDate = null;
    if (currentDate) {
      const current = new Date(currentYear, currentMonth - 1, currentDate);
      const isAvailable = getIsAvailable(current);
      if (isAvailable) {
        initialDate = current;
      }
    }
    if (!initialDate) {
      initialDate = getDates(initialValue)[0] ?? null;
    }

    return initialDate;
  }, [currentDate, currentMonth, currentYear, getIsAvailable, initialValue]);
  const initialActiveDateRef = useRef<Date | null>(initialActiveDate);
  const [activeDate, setActiveDate] = useState<Date | null>(initialActiveDate);
  // console.log('active date:');
  // console.log(activeDate);

  useEffect(() => {
    const initActiveDate = initialActiveDateRef.current;
    if (!initActiveDate) {
      return;
    }

    const firstDateOfCurrentMonth = new Date(currentYear, currentMonth - 1, 1);
    const lastDateOfCurrentMonth = new Date(currentYear, currentMonth, 0);
    if (
      initActiveDate < firstDateOfCurrentMonth ||
      initActiveDate > lastDateOfCurrentMonth
    ) {
      setCalendarYear(initActiveDate.getFullYear());
      setCalendarMonth(initActiveDate.getMonth() + 1);
    }
  }, [currentMonth, currentYear]);

  const reservationBlockListShown = useMemo((): ReservationBlock[] => {
    if (!activeDate) {
      return [];
    }

    const activeDateStr = buildDayjs(activeDate).format('YYYY-MM-DD');

    return reservationBlocks[activeDateStr] ?? [];
  }, [activeDate, reservationBlocks]);

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

  const createDateClickHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isAvailable = getIsAvailable(date);
        if (isAvailable) {
          setActiveDate(date);

          const firstDateOfMonth = new Date(calendarYear, calendarMonth - 1, 1);
          const lastDateOfMonth = new Date(calendarYear, calendarMonth, 0);
          if (date < firstDateOfMonth) {
            handlePrevMonthButtonClick();
          } else if (date > lastDateOfMonth) {
            handleNextMonthButtonClick();
          }
        }
      },
    [
      calendarMonth,
      calendarYear,
      getIsAvailable,
      handleNextMonthButtonClick,
      handlePrevMonthButtonClick,
    ]
  );

  const [hoveredDate, setHoveredDate] = useState<Date | null>(null);
  // console.log('hovered date:');
  // console.log(hoveredDate);

  const createDateMouseOverHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isAvailable = getIsAvailable(date);
        if (!isAvailable) {
          return;
        }

        setHoveredDate(date);
      },
    [getIsAvailable]
  );

  const createDateMouseOutHandler = useCallback(
    (date: Date) =>
      (_event?: React.MouseEvent<HTMLElement>): void => {
        const isAvailable = getIsAvailable(date);
        if (!isAvailable) {
          return;
        }

        setHoveredDate(null);
      },
    [getIsAvailable]
  );

  const getDateDisplayStatus = useCallback(
    (date: Date): DateDisplayStatus => {
      if (date.getTime() === activeDate?.getTime()) {
        return DATE_DISPLAY_STATUS.ACTIVE;
      } else if (date.getTime() === hoveredDate?.getTime()) {
        return DATE_DISPLAY_STATUS.HIGHLIGHTED;
      } else if (getIsAvailable(date)) {
        return DATE_DISPLAY_STATUS.AVAILABLE;
      }

      return DATE_DISPLAY_STATUS.NORMAL;
    },
    [activeDate, getIsAvailable, hoveredDate]
  );

  const initialSelectedReservationBlocks = useMemo(
    (): ReservationBlocks =>
      Object.entries(initialValue).reduce((acc, [key, value]) => {
        acc[key] = value.map((reservation: Reservation) => reservation.block);

        return acc;
      }, {} as ReservationBlocks),
    [initialValue]
  );
  const [selectedReservationBlocks, setSelectedReservationBlocks] =
    useState<ReservationBlocks>(initialSelectedReservationBlocks);

  const selectedReservationBlocksShown = useMemo((): ReservationBlock[] => {
    if (!activeDate) {
      return [];
    }

    const activeDateStr = buildDayjs(activeDate).format('YYYY-MM-DD');

    return selectedReservationBlocks[activeDateStr] ?? [];
  }, [activeDate, selectedReservationBlocks]);

  const initialSelectedPeopleNumLists = useMemo(
    (): PeopleNumLists =>
      Object.entries(initialValue).reduce((acc, [_key, value]) => {
        value.forEach((reservation: Reservation) => {
          acc[reservation.block.reservation_block_token] =
            reservation.peopleNumList;
        });

        return acc;
      }, {} as PeopleNumLists),
    [initialValue]
  );
  const [selectedPeopleNumLists, setSelectedPeopleNumLists] =
    useState<PeopleNumLists>(initialSelectedPeopleNumLists);

  const initialSelectedRoomNums = useMemo(
    (): RoomNums =>
      Object.entries(initialValue).reduce((acc, [_key, value]) => {
        value.forEach((reservation: Reservation) => {
          acc[reservation.block.reservation_block_token] = reservation.roomNum;
        });

        return acc;
      }, {} as RoomNums),
    [initialValue]
  );
  const [selectedRoomNums, setSelectedRoomNums] = useState<RoomNums>(
    initialSelectedRoomNums
  );

  const createReservationBlockClickHandler = useCallback(
    (block: ReservationBlock) =>
      (_event: React.MouseEvent<HTMLDivElement>): void => {
        if (!block.is_available) {
          return;
        }

        const activeDateStr = buildDayjs(activeDate).format('YYYY-MM-DD');
        const prevBlocks = selectedReservationBlocks[activeDateStr] ?? [];
        const prevIsSelected = prevBlocks.some(
          (b: ReservationBlock) =>
            b.reservation_block_token === block.reservation_block_token
        );
        if (isMultiple) {
          const nextBlocks = prevIsSelected
            ? prevBlocks.filter(
                (b: ReservationBlock) =>
                  b.reservation_block_token !== block.reservation_block_token
              )
            : [...prevBlocks, block];
          if (nextBlocks.length === 0) {
            const {
              // eslint-disable-next-line @typescript-eslint/no-unused-vars
              [activeDateStr]: _removedKey,
              ...nextSelectedReservationBlocks
            } = selectedReservationBlocks;
            setSelectedReservationBlocks(nextSelectedReservationBlocks);
          } else {
            setSelectedReservationBlocks({
              ...selectedReservationBlocks,
              [activeDateStr]: nextBlocks,
            });
          }
          if (prevIsSelected) {
            const {
              // eslint-disable-next-line @typescript-eslint/no-unused-vars
              [block.reservation_block_token]: _removedRoomNum,
              ...nextSelectedRoomNum
            } = selectedRoomNums;
            setSelectedRoomNums(nextSelectedRoomNum);

            const {
              // eslint-disable-next-line @typescript-eslint/no-unused-vars
              [block.reservation_block_token]: _removedPeopleNumList,
              ...nextSelectedPeopleNumLists
            } = selectedPeopleNumLists;
            setSelectedPeopleNumLists(nextSelectedPeopleNumLists);
          } else {
            setSelectedRoomNums({
              ...selectedRoomNums,
              [block.reservation_block_token]: 1,
            });

            setSelectedPeopleNumLists({
              ...selectedPeopleNumLists,
              [block.reservation_block_token]: [1],
            });
          }
        } else {
          setSelectedReservationBlocks({
            [activeDateStr]: [block],
          });
          if (!prevIsSelected) {
            setSelectedRoomNums({
              [block.reservation_block_token]: 1,
            });

            setSelectedPeopleNumLists({
              [block.reservation_block_token]: [1],
            });
          }
        }
      },
    [
      activeDate,
      isMultiple,
      selectedPeopleNumLists,
      selectedReservationBlocks,
      selectedRoomNums,
    ]
  );

  const createRoomNumChangeHandler = useCallback(
    (block: ReservationBlock) =>
      (v: number): void => {
        setSelectedRoomNums({
          ...selectedRoomNums,
          [block.reservation_block_token]: v,
        });
        const peopleNumList =
          selectedPeopleNumLists[block.reservation_block_token];
        const nextPeopleNumList = [...Array(v).keys()].map(
          (i: number) => peopleNumList[i] ?? 1
        );
        setSelectedPeopleNumLists({
          ...selectedPeopleNumLists,
          [block.reservation_block_token]: nextPeopleNumList,
        });
      },
    [selectedPeopleNumLists, selectedRoomNums]
  );

  const createPeopleNumChangeHandler = useCallback(
    (block: ReservationBlock, index: number) =>
      (v: number): void => {
        const peopleNumList =
          selectedPeopleNumLists[block.reservation_block_token];
        if (index > peopleNumList.length - 1) {
          return;
        }

        const nextPeopleNumList = [
          ...peopleNumList.slice(0, index),
          v,
          ...peopleNumList.slice(index + 1),
        ];
        setSelectedPeopleNumLists({
          ...selectedPeopleNumLists,
          [block.reservation_block_token]: nextPeopleNumList,
        });
      },
    [selectedPeopleNumLists]
  );

  const selectedReservations = useMemo(
    (): Reservations =>
      Object.entries(selectedReservationBlocks).reduce((acc, [key, value]) => {
        if ((value ?? []).length === 0) {
          return acc;
        }

        const selectedReservationsByDate = value
          .reduce((a: Reservation[], c: ReservationBlock): Reservation[] => {
            const roomNum = selectedRoomNums[c.reservation_block_token] ?? 0;
            const peopleNumList = selectedPeopleNumLists[
              c.reservation_block_token
            ] ?? [0];
            if (
              roomNum > 0 &&
              roomNum <= c.room_num &&
              peopleNumList.length > 0 &&
              peopleNumList.every(
                (num: number) => num > 0 && num <= c.person_capacity
              )
            ) {
              a.push({
                block: c,
                roomNum,
                peopleNumList,
              });
            }

            return a;
          }, [] as Reservation[])
          .sort(
            (a: Reservation, b: Reservation) =>
              buildDayjs(`${key} ${a.block.start_time}`, 'YYYY-MM-DD HH:mm')
                .toDate()
                .getTime() -
              buildDayjs(`${key} ${b.block.start_time}`, 'YYYY-MM-DD HH:mm')
                .toDate()
                .getTime()
          );
        acc[key] = selectedReservationsByDate;

        return acc;
      }, {} as Reservations),
    [selectedPeopleNumLists, selectedReservationBlocks, selectedRoomNums]
  );

  const selectedReservationsCount = useMemo((): number => {
    return Object.values(selectedReservations)
      .map((v: Reservation[]) => v.length)
      .reduce((acc, cur) => acc + cur, 0);
  }, [selectedReservations]);

  const isDirty = useMemo((): boolean => {
    const initKeys = Object.keys(initialValue);
    const selectedKeys = Object.keys(selectedReservations);
    if (initKeys.length !== selectedKeys.length) {
      return true;
    }

    for (const key of selectedKeys) {
      const initValue = initialValue[key];
      const selectedValue = selectedReservations[key];
      if (!initValue || initValue.length !== selectedValue.length) {
        return true;
      }

      const isChanged = selectedValue.some((v: Reservation) => {
        const correspondingBlock = initValue.find(
          (b: Reservation) =>
            b.block.reservation_block_token === v.block.reservation_block_token
        );
        if (!correspondingBlock) {
          return true;
        }

        if (v.roomNum !== correspondingBlock.roomNum) {
          return true;
        }

        if (
          v.peopleNumList.some(
            (num: number, i: number) =>
              num !== correspondingBlock.peopleNumList[i]
          )
        ) {
          return true;
        }

        return false;
      });
      if (isChanged) {
        return true;
      }
    }

    return false;
  }, [initialValue, selectedReservations]);

  const isReady = selectedReservationsCount > 0 && isDirty;

  const handleCloseButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      close();
    },
    [close]
  );

  const handleConfirmButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      onChange(selectedReservations);
      handleCloseButtonClick();
    },
    [handleCloseButtonClick, onChange, selectedReservations]
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
              <div
                className="calendar_header"
                style={{ marginBottom: 4, paddingTop: 6, paddingBottom: 6 }}
              >
                <button
                  type="button"
                  className="prev_wrap"
                  onClick={handlePrevMonthButtonClick}
                >
                  <img src="/static/common/images/prev.svg" alt="prev" />
                </button>
                <div className="text-lg font-bold">
                  {calendarYear}年{calendarMonth}月
                </div>
                <button
                  type="button"
                  className="prev_wrap"
                  onClick={handleNextMonthButtonClick}
                >
                  <img src="/static/common/images/next.svg" alt="next" />
                </button>
              </div>
              <div className="pb-0.5">
                <table className="calendar_table">
                  <tbody className="cursor-default">
                    <tr>
                      {DAY_OF_WEEK_STRINGS.map(
                        (dayOfWeekString: DayOfWeekString) => (
                          <td
                            key={dayOfWeekString}
                            className="dayWeek"
                            style={{ height: 22 }}
                          >
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
                              style={{ height: 36 }}
                            >
                              <div
                                className="flex items-center justify-center"
                                style={{
                                  width: 32,
                                  height: 32,
                                  margin: 'auto',
                                  ...(isOutOfMonth
                                    ? {
                                        color: disabledColor,
                                      }
                                    : {}),
                                  ...(isPast
                                    ? {
                                        color: disabledColor,
                                      }
                                    : {}),
                                  ...(status === DATE_DISPLAY_STATUS.AVAILABLE
                                    ? {
                                        border: `2px solid ${activeColor}`,
                                        borderRadius: '9999px',
                                        cursor: 'pointer',
                                      }
                                    : {}),
                                  ...(status === DATE_DISPLAY_STATUS.HIGHLIGHTED
                                    ? {
                                        border: `2px solid ${activeColor}`,
                                        borderRadius: '9999px',
                                        backgroundColor: highlightedColor,
                                        cursor: 'pointer',
                                      }
                                    : {}),
                                  ...(status === DATE_DISPLAY_STATUS.ACTIVE
                                    ? {
                                        borderRadius: '9999px',
                                        backgroundColor: activeColor,
                                        color: 'white',
                                        cursor: 'pointer',
                                      }
                                    : {}),
                                }}
                                onClick={createDateClickHandler(date)}
                                onMouseOver={createDateMouseOverHandler(date)}
                                onMouseOut={createDateMouseOutHandler(date)}
                              >
                                {`0${date.getDate()}`.slice(-2)}
                              </div>
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
          <div
            className="border-b border-gray-200 border-solid"
            style={{ marginBottom: 12 }}
          >
            {reservationBlockListShown.length > 0 && (
              <>
                <div className="flex items-center px-3 bg-gray-100 border-t border-b border-gray-200 border-solid py-1.5">
                  <div className="text-xs font-medium">
                    {buildDayjs(activeDate).format('YYYY年M月D日の')}
                    予約時間（時間を選択してください）
                  </div>
                </div>
                <div
                  className="px-3 overflow-scroll"
                  style={{ maxHeight: `calc(${100 * vh}px - 430px)` }}
                >
                  {reservationBlockListShown.map(
                    (block: ReservationBlock, index: number) => (
                      <div
                        key={block.reservation_block_token}
                        className="flex flex-col px-3 py-2 border-b border-gray-200 border-solid"
                        style={
                          index === reservationBlockListShown.length - 1
                            ? { borderBottomWidth: 0 }
                            : {}
                        }
                      >
                        <div
                          className={[
                            'flex items-center justify-between w-full',
                            block.is_available && 'cursor-pointer',
                          ]
                            .filter(Boolean)
                            .join(' ')}
                          onClick={createReservationBlockClickHandler(block)}
                        >
                          <div className="flex items-center">
                            <div
                              className="flex items-center justify-center"
                              style={{ width: 24, height: 24 }}
                            >
                              <CheckIcon
                                className="w-5 h-5"
                                style={{
                                  marginTop: 2,
                                  color: selectedReservationBlocksShown.some(
                                    (b: ReservationBlock) =>
                                      b.reservation_block_token ===
                                      block.reservation_block_token
                                  )
                                    ? '#000'
                                    : '#e5e7eb',
                                }}
                              />
                            </div>
                            <div style={{ marginLeft: 12 }}>
                              <div className="text-sm">
                                {block.start_time} - {block.end_time}
                              </div>
                            </div>
                          </div>
                          <div>
                            <div className="text-sm">
                              (定員数: {block.person_capacity}名)
                              <span style={{ marginLeft: 12 }}>
                                ¥{block.price.toLocaleString()}
                              </span>
                            </div>
                          </div>
                        </div>
                        {selectedReservationBlocksShown.some(
                          (b: ReservationBlock) =>
                            b.reservation_block_token ===
                            block.reservation_block_token
                        ) && (
                          <>
                            <div
                              className="flex items-center justify-between border-t border-gray-200 border-solid"
                              style={{
                                marginTop: 8,
                                marginBottom: -4,
                                marginLeft: 36,
                                paddingTop: 4,
                              }}
                            >
                              <div>
                                <div className="text-sm">部屋数</div>
                              </div>
                              <PlusMinusInput
                                value={
                                  selectedRoomNums[
                                    block.reservation_block_token
                                  ]
                                }
                                min={1}
                                max={block.room_num}
                                onChange={createRoomNumChangeHandler(block)}
                              />
                            </div>
                            {[
                              ...Array(
                                selectedRoomNums[block.reservation_block_token]
                              ).keys(),
                            ].map((roomIndex: number) => (
                              <div
                                key={`${block.reservation_block_token}_person_num_${roomIndex}`}
                                className="flex items-center justify-between border-t border-gray-200 border-solid"
                                style={{
                                  marginTop: 8,
                                  marginBottom: -4,
                                  marginLeft: 36,
                                  paddingTop: 4,
                                }}
                              >
                                <div>
                                  <div className="text-sm">
                                    ご利用人数
                                    {selectedRoomNums[
                                      block.reservation_block_token
                                    ] > 1
                                      ? ` (${roomIndex + 1}部屋目)`
                                      : ''}
                                  </div>
                                </div>
                                <PlusMinusInput
                                  value={
                                    selectedPeopleNumLists[
                                      block.reservation_block_token
                                    ][roomIndex]
                                  }
                                  min={1}
                                  max={block.person_capacity}
                                  onChange={createPeopleNumChangeHandler(
                                    block,
                                    roomIndex
                                  )}
                                />
                              </div>
                            ))}
                          </>
                        )}
                      </div>
                    )
                  )}
                </div>
              </>
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
              disabled={!isReady}
              className={[
                'px-3 text-lg font-medium leading-normal text-white rounded py-1.5',
                !isReady && 'cursor-not-allowed',
              ]
                .filter(Boolean)
                .join(' ')}
              style={{
                width: '47%',
                backgroundColor: isReady
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
      {isReservationBlocksFetching && (
        <div className="fixed top-0 bottom-0 left-0 right-0 flex flex-col items-center justify-center bg-black bg-opacity-50 z-100">
          <Spinner />
        </div>
      )}
    </>
  );
};

export default ReservationCalendarModal;
