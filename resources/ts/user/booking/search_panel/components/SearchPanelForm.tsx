/* eslint-disable jsx-a11y/no-onchange */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, {
  FC,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import ReactDOM from 'react-dom';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import $ from 'jquery';
import 'gasparesganga-jquery-loading-overlay';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import { useQueryParams } from '../../../../common/hooks/use-query-params';
import { useBrowserBackWithNoCache } from '../../../../common/hooks/use-browser-back-with-no-cache';
import { buildDayjs } from '../../../../common/utils/date';
import { formatDate } from '../../../../common/utils/scripts';
import api from '../../../../common/utils/api';
import {
  CheckinTimeListResponseData,
  DayuseSearchDataRequestParams,
  Hotel,
  HotelNote,
  KidsPolicy,
  PlanDetail,
  PlanRoomTypesResponseData,
  PlanSearchError,
  Room,
  RoomInfo,
  RoomTypeDetail,
  RoomTypes,
  SearchData,
  SearchDataRequestParams,
  SearchDataResponseData,
  SelectedRoomCancelResponseData,
  SelectedRoomDataResponseData,
  StayTimeListResponseData,
} from '../../datatypes';
import { DEFAULT_ERROR_MESSAGE } from '../constants';
import { CalendarIcon, ClockIcon, UsersIcon } from '@heroicons/react/solid';
import DateRangeInput from '../../components/DateRangeInput';
import DateInput from './DateInput';
import { RoomPeople } from '../../components/PeopleNumberSelectModal';
import PeopleNumberInput from '../../components/PeopleNumberInput';
import ToggleSwitch from '../../components/ToggleSwitch';
import RoomPlanSelect from './RoomPlanSelect';
import SelectedRoomsDisplay from './SelectedRoomsDisplay';
import Spinner from '../../components/Spinner';
import HotelPolicyModalLauncher from '../../components/HotelPolicyModalLauncher';

type Props = {
  staySearchDataUrl?: string;
  dayuseSearchDataUrl?: string;
  maxChildAge: number;
  maxAdultNum: number;
  maxChildNum: number;
  kidsPolicies: KidsPolicy[];
  lpParam?: string;
  closeButtonImageUrl?: string;
  showDayuseSwitch: boolean;
  checkinTimeUrl?: string;
  stayTimeUrl?: string;
  errors?: PlanSearchError;
  closeImageUrl?: string;
  planDetailUrl?: string;
  restaurantImageUrl?: string;
  planRoomTypeUrl?: string;
  selectedRoomDataUrl?: string;
  roomDetailUrl?: string;
  infoInputUrl?: string;
  selectedRoomCancelUrl?: string;
  hotel?: Hotel;
  hotelNotes?: HotelNote[];
};

const SearchPanelForm: FC<Props> = ({
  staySearchDataUrl,
  dayuseSearchDataUrl,
  maxChildAge,
  maxAdultNum,
  maxChildNum,
  kidsPolicies,
  lpParam,
  closeButtonImageUrl,
  showDayuseSwitch,
  checkinTimeUrl,
  stayTimeUrl,
  errors,
  closeImageUrl,
  planDetailUrl,
  restaurantImageUrl,
  planRoomTypeUrl,
  selectedRoomDataUrl,
  roomDetailUrl,
  infoInputUrl,
  selectedRoomCancelUrl,
  hotel,
  hotelNotes,
}) => {
  const queryParams = useQueryParams();

  const now = buildDayjs();
  const today = new Date(now.year(), now.month(), now.date());
  const tomorrow = new Date(now.year(), now.month(), now.date() + 1);

  const [dates, setDates] = useState<Date[]>([today, tomorrow]);

  const [isDayuse, setIsDayuse] = useState<boolean>(
    queryParams['dayuse'] === true
  );
  const [dayuseDate, setDayuseDate] = useState<Date>(today);
  const [checkinTimeList, setCheckinTimeList] = useState<string[]>([]);
  const [checkinTime, setCheckinTime] = useState<string>('');
  const [stayTimeList, setStayTimeList] = useState<number[]>([]);
  const [stayTime, setStayTime] = useState<string>('');
  const [message, setMessage] = useState<string>('');

  const roomPeopleDefault = {
    adultNum: 1,
    childNums: new Array(kidsPolicies.length).fill(0),
  };
  const [roomPeoples, setRoomPeoples] = useState<RoomPeople[]>([
    roomPeopleDefault,
  ]);

  const [isSearchConditionModified, setIsSearchConditionModified] =
    useState<boolean>(false);

  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const submitButtonRef = useRef<HTMLButtonElement | null>(null);

  const [searchData, setSearchData] = useState<SearchData | null>(null);
  const [roomTypesList, setRoomTypesList] = useState<RoomTypes[] | null>(null);

  const [selectedPlanDetail, setSelectedPlanDetail] =
    useState<PlanDetail | null>(null);
  const [selectedRooms, setSelectedRooms] = useState<(Room | null)[] | null>(
    null
  );
  const [selectedRoomDataResponses, setSelectedRoomDataResponses] = useState<
    (SelectedRoomDataResponseData | null)[] | null
  >(null);

  const [isCheckinTimeListFetching, setIsCheckinTimeListFetching] =
    useState<boolean>(false);
  const [isStayTimeListFetching, setIsStayTimeListFetching] =
    useState<boolean>(false);
  const [isSearchDataFetching, setIsSearchDataFetching] =
    useState<boolean>(false);
  const [isRoomTypesListFetching, setIsRoomTypesListFetching] =
    useState<boolean>(false);
  const [isSelectedRoomDataFetching, setIsSelectedRoomDataFetching] =
    useState<boolean>(false);
  const [isFirstRoomWithPlanRegistering, setIsFirstRoomWithPlanRegistering] =
    useState<boolean>(false);
  const [isRoomCancelRequesting, setIsRoomCancelRequesting] =
    useState<boolean>(false);

  useBrowserBackWithNoCache();

  const currentSelectIndex = useMemo((): number => {
    if (!selectedRooms?.length) {
      return 0;
    }

    return selectedRooms.findIndex((room: Room | null) => !room);
  }, [selectedRooms]);

  const roomsCount = useMemo((): number => {
    if (!roomTypesList?.[0]?.stayAbleRooms) {
      return 0;
    }

    return roomTypesList[0].stayAbleRooms.length;
  }, [roomTypesList]);

  const handleDayuseSwitchChanged = useCallback((checked: boolean): void => {
    setIsDayuse(checked);
  }, []);

  const handleDatesChange = useCallback((value: Date[]): void => {
    setDates(value);
    setIsSearchConditionModified(true);
  }, []);

  const handleDayuseDateChange = useCallback((value: Date): void => {
    setStayTime('');
    setCheckinTime('');
    setDayuseDate(value);
    setIsSearchConditionModified(true);
  }, []);

  const handleCheckinTimeChange = useCallback(
    (event: React.ChangeEvent<HTMLSelectElement>): void => {
      setStayTime('');
      setCheckinTime(event.target.value);
      setIsSearchConditionModified(true);
    },
    []
  );

  const handleStayTimeChange = useCallback(
    (event: React.ChangeEvent<HTMLSelectElement>): void => {
      setStayTime(event.target.value);
      setIsSearchConditionModified(true);
    },
    []
  );

  const handleRoomPeoplesChange = useCallback((value: RoomPeople[]): void => {
    setRoomPeoples(value);
    setIsSearchConditionModified(true);
  }, []);

  const csrfToken = useCsrfToken();

  const fetchCheckinTimeList = useCallback(async (): Promise<void> => {
    if (!checkinTimeUrl) {
      return;
    }

    setIsCheckinTimeListFetching(true);
    try {
      const data = (await api.post(checkinTimeUrl, {
        checkin_date: formatDate(dayuseDate),
        url_param: lpParam ?? '',
        _token: csrfToken,
      })) as CheckinTimeListResponseData;
      if (data.res === 'ok') {
        const timeList = data.min_max.filter((time: string) =>
          buildDayjs(time, 'YYYY/MM/DD HH:mm').isAfter(buildDayjs())
        );
        setCheckinTimeList(timeList);
      } else {
        alert(data.message ?? DEFAULT_ERROR_MESSAGE);
      }
    } catch (_error) {
      alert(DEFAULT_ERROR_MESSAGE);
    } finally {
      setIsCheckinTimeListFetching(false);
    }
  }, [checkinTimeUrl, csrfToken, dayuseDate, lpParam]);

  useEffect(() => {
    if (isSubmitting || !isDayuse) {
      return;
    }

    setCheckinTimeList([]);
    if (!checkinTimeUrl || !csrfToken || !lpParam || !dayuseDate) {
      return;
    }

    fetchCheckinTimeList();
  }, [
    checkinTimeUrl,
    csrfToken,
    dayuseDate,
    fetchCheckinTimeList,
    isDayuse,
    isSubmitting,
    lpParam,
  ]);

  const fetchStayTimeList = useCallback(async (): Promise<void> => {
    if (!stayTimeUrl) {
      return;
    }

    setIsStayTimeListFetching(true);
    try {
      const data = (await api.post(stayTimeUrl, {
        checkin_date: formatDate(dayuseDate),
        checkin_date_time: checkinTime,
        url_param: lpParam ?? '',
        _token: csrfToken,
      })) as StayTimeListResponseData;
      if (data.res === 'ok') {
        setStayTimeList(data.stay_time);
      } else {
        alert(data.message ?? DEFAULT_ERROR_MESSAGE);
      }
    } catch (_error) {
      alert(DEFAULT_ERROR_MESSAGE);
    } finally {
      setIsStayTimeListFetching(false);
    }
  }, [checkinTime, csrfToken, dayuseDate, lpParam, stayTimeUrl]);

  useEffect(() => {
    if (isSubmitting || !isDayuse) {
      return;
    }

    setStayTimeList([]);
    if (!stayTimeUrl || !csrfToken || !lpParam || !dayuseDate || !checkinTime) {
      return;
    }

    fetchStayTimeList();
  }, [
    checkinTime,
    checkinTimeUrl,
    csrfToken,
    dayuseDate,
    fetchCheckinTimeList,
    fetchStayTimeList,
    isDayuse,
    isSubmitting,
    lpParam,
    stayTimeUrl,
  ]);

  const childNumParams = useMemo(
    (): { [x: string]: string[] } =>
      kidsPolicies.reduce(
        (acc: { [x: string]: string[] }, cur: KidsPolicy, index: number) => {
          const key = `kidstart_${cur.age_start}`;
          const value = roomPeoples.map((roomPeople: RoomPeople) =>
            roomPeople.childNums[index].toString()
          );
          acc[key] = value;

          return acc;
        },
        {}
      ),
    [kidsPolicies, roomPeoples]
  );

  const childNumSum = useMemo(
    (): number =>
      roomPeoples.reduce(
        (acc: number, cur: RoomPeople): number =>
          cur.childNums.reduce((a: number, c: number): number => a + c, acc),
        0
      ),
    [roomPeoples]
  );

  const fetchSearchData = useCallback(
    async (params: SearchDataRequestParams): Promise<void> => {
      if (!staySearchDataUrl) {
        return;
      }

      setIsSearchDataFetching(true);
      try {
        const data = (await api.post(staySearchDataUrl, {
          ...params,
        })) as SearchDataResponseData;
        if (data.res === 'ok') {
          setMessage('')
          setSearchData(data.searchData);
        } else if (data.message) {
          setMessage(data.message);
        } else if (data.error) {
          if (typeof data.error !== 'string') {
            const errorMessages = Object.values(data.error)
              .map((value: string[] | undefined) => value?.[0])
              .filter((value: string | undefined) => !!value);
            alert(errorMessages[0]);
          } else {
            alert(data.error);
          }
        } else {
          alert(DEFAULT_ERROR_MESSAGE);
        }
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
      } finally {
        setIsSearchDataFetching(false);
      }
    },
    [staySearchDataUrl]
  );

  useEffect(() => {
    if (isSubmitting || isDayuse) {
      return;
    }

    setSearchData(null);
    if (
      !staySearchDataUrl ||
      !csrfToken ||
      !lpParam ||
      !dates[0] ||
      !dates[1] ||
      !roomPeoples[0]?.adultNum
    ) {
      return;
    }

    const params = {
      _token: csrfToken,
      checkin_date: formatDate(dates[0]),
      checkout_date: formatDate(dates[1]),
      adult_num: roomPeoples.map((roomPeople: RoomPeople) =>
        roomPeople.adultNum.toString()
      ),
      ...childNumParams,
      url_param: lpParam,
      child_num: childNumSum.toString(),
      room_num: roomPeoples.length.toString(),
    };
    fetchSearchData(params);
  }, [
    childNumParams,
    childNumSum,
    csrfToken,
    dates,
    fetchSearchData,
    isDayuse,
    isSubmitting,
    kidsPolicies,
    lpParam,
    roomPeoples,
    staySearchDataUrl,
  ]);

  const fetchDayuseSearchData = useCallback(
    async (params: DayuseSearchDataRequestParams): Promise<void> => {
      if (!dayuseSearchDataUrl) {
        return;
      }

      setIsSearchDataFetching(true);
      try {
        const data = (await api.post(dayuseSearchDataUrl, {
          ...params,
        })) as SearchDataResponseData;
        if (data.res === 'ok') {
          setSearchData(data.searchData);
        } else if (data.message) {
          alert(data.message);
        } else if (data.error) {
          if (typeof data.error !== 'string') {
            const errorMessages = Object.values(data.error)
              .map((value: string[] | undefined) => value?.[0])
              .filter((value: string | undefined) => !!value);
            alert(errorMessages[0]);
          } else {
            alert(data.error);
          }
        } else {
          alert(DEFAULT_ERROR_MESSAGE);
        }
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
      } finally {
        setIsSearchDataFetching(false);
      }
    },
    [dayuseSearchDataUrl]
  );

  useEffect(() => {
    if (isSubmitting || !isDayuse) {
      return;
    }

    setSearchData(null);
    if (
      !dayuseSearchDataUrl ||
      !csrfToken ||
      !lpParam ||
      !dayuseDate ||
      !checkinTime ||
      !stayTime ||
      !roomPeoples[0]?.adultNum
    ) {
      return;
    }

    const params = {
      _token: csrfToken,
      checkin_date: formatDate(dayuseDate),
      checkin_date_time: checkinTime,
      adult_num: roomPeoples.map((roomPeople: RoomPeople) =>
        roomPeople.adultNum.toString()
      ),
      ...childNumParams,
      url_param: lpParam,
      child_num: childNumSum.toString(),
      room_num: roomPeoples.length.toString(),
      stay_time: stayTime,
    };
    fetchDayuseSearchData(params);
  }, [
    checkinTime,
    childNumParams,
    childNumSum,
    csrfToken,
    dayuseDate,
    dayuseSearchDataUrl,
    fetchDayuseSearchData,
    isDayuse,
    isSubmitting,
    kidsPolicies,
    lpParam,
    roomPeoples,
    stayTime,
  ]);

  const fetchRoomTypesList = useCallback(async (): Promise<void> => {
    if (!planRoomTypeUrl || !searchData?.plans?.[0]) {
      return;
    }

    setIsRoomTypesListFetching(true);
    try {
      // const responseList = (await Promise.all(
      //   searchData.plans.map((plan: Plan) =>
      //     api.post(planRoomTypeUrl, {
      //       plan_token: plan.plan_token,
      //       _token: csrfToken,
      //     })
      //   )
      // )) as PlanRoomTypesResponseData[];
      // WARNING: use last plan to fetch room detail

      const responseList = [];
      for (const plan of searchData.plans) {
        const responseData = (await api.post(planRoomTypeUrl, {
          plan_token: plan.plan_token,
          _token: csrfToken,
        })) as PlanRoomTypesResponseData;
        responseList.push(responseData);
      }
      if (
        responseList.every(
          (data: PlanRoomTypesResponseData) => data.res === 'ok'
        )
      ) {
        setRoomTypesList(
          responseList.map((data: PlanRoomTypesResponseData) => data.roomTypes)
        );
        const count = responseList[0].roomTypes.stayAbleRooms.length;
        setSelectedRooms(new Array(count).fill(null));
        setSelectedRoomDataResponses(new Array(count).fill(null));
      } else {
        const errorResponse = responseList.find(
          (data: PlanRoomTypesResponseData) => data.res !== 'ok'
        );
        alert(errorResponse?.message ?? DEFAULT_ERROR_MESSAGE);
      }
    } catch (_error) {
      alert(DEFAULT_ERROR_MESSAGE);
    } finally {
      setIsRoomTypesListFetching(false);
    }
  }, [csrfToken, planRoomTypeUrl, searchData?.plans]);

  useEffect(() => {
    setSelectedRoomDataResponses(null);
    setSelectedRooms(null);
    setSelectedPlanDetail(null);
    setRoomTypesList(null);
    fetchRoomTypesList();
  }, [fetchRoomTypesList, searchData]);

  const ageNumbsKana = useMemo((): string[] | null => {
    if (!roomTypesList?.[0]) {
      return null;
    }

    return roomTypesList[0].ageNumsKana;
  }, [roomTypesList]);

  const selectedPlanRoomTypes = useMemo((): RoomTypes | null => {
    if (!roomTypesList || !selectedPlanDetail?.plan_token) {
      return null;
    }

    return (
      roomTypesList.find(
        (type: RoomTypes) => type.planToken === selectedPlanDetail.plan_token
      ) ?? null
    );
  }, [roomTypesList, selectedPlanDetail?.plan_token]);

  const roomInfos = useMemo((): RoomInfo[] | null => {
    if (selectedPlanRoomTypes) {
      if (
        currentSelectIndex < 0 ||
        currentSelectIndex > selectedPlanRoomTypes.stayAbleRooms.length - 1
      ) {
        return null;
      }

      return selectedPlanRoomTypes.stayAbleRooms[currentSelectIndex].map(
        (room: Room) => ({
          amounts: {
            [selectedPlanRoomTypes.planToken]: room.amount,
          },
          currentSelectIndex,
          room,
          planDetails: [
            {
              ...selectedPlanRoomTypes.targetPlan,
              plan_token: selectedPlanRoomTypes.planToken,
            },
          ],
        })
      );
    }

    return (
      roomTypesList?.reduce(
        (acc: RoomInfo[], cur: RoomTypes) =>
          (cur.stayAbleRooms[0] ?? []).reduce((a: RoomInfo[], c: Room) => {
            const appendedInfo = a.find(
              (info: RoomInfo) => info.room.room_type_id === c.room_type_id
            );
            if (appendedInfo) {
              appendedInfo.amounts[cur.planToken] = c.amount;
              appendedInfo.planDetails.push({
                ...cur.targetPlan,
                plan_token: cur.planToken,
              });
              appendedInfo.room.room_token = c.room_token;
              // WARNING: adding wrong room_token to fetch room detail later
            } else {
              const appendingInfo = {
                amounts: {
                  [cur.planToken]: c.amount,
                },
                currentSelectIndex,
                room: {
                  ...c,
                  // room_token: undefined,
                  // WARNING: adding wrong room_token to fetch room detail later
                },
                planDetails: [
                  {
                    ...cur.targetPlan,
                    plan_token: cur.planToken,
                  },
                ],
              };
              a.push(appendingInfo);
            }

            return a;
          }, acc),
        []
      ) ?? null
    );
  }, [currentSelectIndex, roomTypesList, selectedPlanRoomTypes]);

  const resetRoomSelection = useCallback(
    (index: number): void => {
      const rooms = selectedRooms ?? [];
      setSelectedRooms([
        ...rooms.slice(0, index),
        null,
        ...rooms.slice(index + 1),
      ]);

      const responses = selectedRoomDataResponses ?? [];
      setSelectedRoomDataResponses([
        ...responses.slice(0, index),
        null,
        ...responses.slice(index + 1),
      ]);
    },
    [selectedRoomDataResponses, selectedRooms]
  );

  const fetchSelectedRoomData = useCallback(
    async (index: number, roomToken?: string): Promise<void> => {
      if (!selectedRoomDataUrl || !csrfToken || !roomToken) {
        return;
      }

      setIsSelectedRoomDataFetching(true);
      try {
        const data = (await api.post(selectedRoomDataUrl, {
          room_token: roomToken,
          _token: csrfToken,
        })) as SelectedRoomDataResponseData;
        if (data.res === 'ok') {
          const responses = selectedRoomDataResponses ?? [];
          setSelectedRoomDataResponses([
            ...responses.slice(0, index),
            data,
            ...responses.slice(index + 1),
          ]);
        } else {
          alert(data.message ?? DEFAULT_ERROR_MESSAGE);
          resetRoomSelection(index);
        }
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
      } finally {
        setIsSelectedRoomDataFetching(false);
      }
    },
    [
      selectedRoomDataUrl,
      csrfToken,
      selectedRoomDataResponses,
      resetRoomSelection,
    ]
  );

  const selectRoom = useCallback(
    async (index: number, room: Room): Promise<void> => {
      await fetchSelectedRoomData(index, room?.room_token);

      const rooms = selectedRooms ?? [];
      setSelectedRooms([
        ...rooms.slice(0, index),
        room,
        ...rooms.slice(index + 1),
      ]);
    },
    [fetchSelectedRoomData, selectedRooms]
  );

  const handleFormSubmit = useCallback(
    (event?: React.FormEvent<HTMLFormElement>): void => {
      if (!infoInputUrl) {
        return;
      }

      if (submitButtonRef.current) {
        setIsSubmitting(true);
        if (event) {
          $(submitButtonRef.current).LoadingOverlay('show');
        } else {
          window.location.href = infoInputUrl;
        }
      }
    },
    [infoInputUrl]
  );

  const selectFirstRoomWithPlanDetail = useCallback(
    async (room: Room, planDetail: PlanDetail): Promise<void> => {
      if (!planRoomTypeUrl || !roomTypesList || !planDetail.plan_token) {
        return;
      }

      setIsFirstRoomWithPlanRegistering(true);
      try {
        const data = (await api.post(planRoomTypeUrl, {
          plan_token: planDetail.plan_token,
          _token: csrfToken,
        })) as PlanRoomTypesResponseData;
        const nextPlanDetail = {
          ...data.roomTypes.targetPlan,
          plan_token: data.roomTypes.planToken,
        };
        const roomWithToken = data.roomTypes.stayAbleRooms[0].find(
          (r: Room) => r.room_type_id === room.room_type_id
        );
        if (!roomWithToken?.room_token) {
          return;
        }

        setRoomTypesList([data.roomTypes, ...roomTypesList.slice(1)]);
        setSelectedPlanDetail(nextPlanDetail);
        await selectRoom(0, roomWithToken);
        if (roomsCount === 1) {
          handleFormSubmit();
        }
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
      } finally {
        setIsFirstRoomWithPlanRegistering(false);
      }
    },
    [
      csrfToken,
      handleFormSubmit,
      planRoomTypeUrl,
      roomTypesList,
      roomsCount,
      selectRoom,
    ]
  );

  const cancelRoom = useCallback(
    async (index: number): Promise<void> => {
      const roomToken = selectedRooms?.[index]?.room_token;
      if (!selectedRoomCancelUrl || !csrfToken || !roomToken) {
        return;
      }

      setIsRoomCancelRequesting(true);
      try {
        const data = (await api.post(selectedRoomCancelUrl, {
          room_token: roomToken,
          room_num: index,
          _token: csrfToken,
        })) as SelectedRoomCancelResponseData;
        if (data.res === 'ok') {
          const rooms = selectedRooms ?? [];
          const canceledRoom = rooms[index];
          const nextRooms = [
            ...rooms.slice(0, index),
            null,
            ...rooms.slice(index + 1),
          ];
          setSelectedRooms(nextRooms);

          const shownRoomTokens = data.showRoomTokens;
          const responses = selectedRoomDataResponses ?? [];
          const roomDataResponses = responses.map(
            (
              response: SelectedRoomDataResponseData | null,
              i: number
            ): SelectedRoomDataResponseData | null => {
              if (i === index || !response) {
                return null;
              }

              return {
                ...response,
                isInStock:
                  // HACK: To deal with incorrect showRoomTokens.
                  canceledRoom &&
                  response.selectedRoomData.roomDetail.room_type_id ===
                    canceledRoom.room_type_id
                    ? true
                    : response.isInStock,
                hideRoomTokens: response.hideRoomTokens.filter(
                  (token: string) => !shownRoomTokens.includes(token)
                ),
              };
            }
          );
          setSelectedRoomDataResponses(roomDataResponses);

          const isSomeSelected = nextRooms.some(
            (room: Room | null) => room != null
          );
          if (!isSomeSelected) {
            setSelectedPlanDetail(null);
          }
        } else {
          alert(data.message ?? DEFAULT_ERROR_MESSAGE);
        }
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
      } finally {
        setIsRoomCancelRequesting(false);
      }
    },
    [csrfToken, selectedRoomCancelUrl, selectedRoomDataResponses, selectedRooms]
  );

  const hiddenRoomTokens = useMemo((): string[][] => {
    if (!selectedPlanRoomTypes?.stayAbleRooms?.length) {
      return [[]];
    }

    const responses = selectedRoomDataResponses ?? [];

    return [...Array(selectedPlanRoomTypes.stayAbleRooms.length).keys()].map(
      (index: number): string[] =>
        [
          ...responses.slice(0, index),
          null,
          ...responses.slice(index + 1),
        ].reduce(
          (
            acc: string[],
            cur: SelectedRoomDataResponseData | null
          ): string[] => [
            ...acc,
            ...(cur?.isInStock === false ? cur.hideRoomTokens : []),
          ],
          []
        )
    );
  }, [selectedPlanRoomTypes?.stayAbleRooms?.length, selectedRoomDataResponses]);

  const allSelected = useMemo(() => {
    const responses = selectedRoomDataResponses ?? [];

    return (
      responses.every(
        (data: SelectedRoomDataResponseData | null) => data != null
      ) &&
      responses.some(
        (data: SelectedRoomDataResponseData | null) => data?.is_all_selected
      )
    );
  }, [selectedRoomDataResponses]);

  const selectedRoomTypeDetails = useMemo(():
    | (RoomTypeDetail | null)[]
    | null => {
    if (!selectedRoomDataResponses) {
      return null;
    }

    return selectedRoomDataResponses.map(
      (res: SelectedRoomDataResponseData | null) =>
        res?.selectedRoomData ?? null
    );
  }, [selectedRoomDataResponses]);

  return (
    <>
      {showDayuseSwitch && (
        <div className="mt-5 mb-2 text-sm text-gray-900">
          <ToggleSwitch
            text="日帰りプランを検索"
            checked={isDayuse}
            onChange={handleDayuseSwitchChanged}
          />
        </div>
      )}
      <div style={{ boxShadow: '0 6px 4px -4px rgb(0, 0, 0, 0.25)' }}>
        <div className="static-search_panel__inner">
          <div className="mb-2.5 static-search_panel__item_wrap">
            <div className="mb-1.5 static-search_panel__item_text static-search_panel__item_title">
              <div className="flex items-center justify-start">
                <div className="mr-2.5">
                  <CalendarIcon className="w-6 h-6 opacity-30" />
                </div>
                <div>
                  {isDayuse ? 'ご利用日' : 'チェックイン - チェックアウト'}
                </div>
              </div>
            </div>
            <div className="static-search_panel__item_input_area">
              <div>
                {isDayuse ? (
                  <DateInput
                    value={dayuseDate}
                    date={today}
                    leadText="ご利用日"
                    closeButtonImageUrl={closeButtonImageUrl}
                    onChange={handleDayuseDateChange}
                  />
                ) : (
                  <DateRangeInput
                    value={dates}
                    date={today}
                    startDateLeadText="チェックイン"
                    endDateLeadText="チェックアウト"
                    closeButtonImageUrl={closeButtonImageUrl}
                    onChange={handleDatesChange}
                  />
                )}
              </div>
            </div>
            {errors?.checkin_date && errors.checkin_date.length > 0 && (
              <p className="common_form_error">{errors.checkin_date[0]}</p>
            )}
            {errors?.checkout_date && errors.checkout_date.length > 0 && (
              <p className="common_form_error">{errors.checkout_date[0]}</p>
            )}
          </div>
          {isDayuse && (
            <div className="mb-2.5 static-search_panel__item_wrap">
              <div className="mb-1.5 static-search_panel__item_text static-search_panel__item_title">
                <div className="flex items-center justify-start">
                  <div className="mr-2.5">
                    <ClockIcon className="w-6 h-6 opacity-30" />
                  </div>
                  <div>チェックイン予定時間 / 滞在時間</div>
                </div>
              </div>
              <div className="flex items-center justify-start">
                <div style={{ width: 180 }}>
                  <select
                    className="w-full static-search_panel__select_box"
                    style={{ font: '400 13.3333px Arial' }}
                    value={checkinTime}
                    onChange={handleCheckinTimeChange}
                  >
                    <option value="">未選択</option>
                    <>
                      {checkinTimeList.map((time: string) => (
                        <option key={time} value={time}>
                          {time}
                        </option>
                      ))}
                    </>
                  </select>
                </div>
                {stayTimeList.length > 0 && (
                  <>
                    <div className="mx-2">/</div>
                    <div style={{ width: 108 }}>
                      <select
                        className="w-full static-search_panel__select_box"
                        style={{ font: '400 13.3333px Arial' }}
                        value={stayTime}
                        onChange={handleStayTimeChange}
                      >
                        <option value="">未選択</option>
                        <>
                          {stayTimeList.map((time: number) => (
                            <option key={time} value={time.toString()}>
                              {time}時間
                            </option>
                          ))}
                        </>
                      </select>
                    </div>
                  </>
                )}
              </div>
            </div>
          )}
          <div
            className="static-search_panel__item_wrap"
            style={{ marginBottom: 0 }}
          >
            <div className="mb-1.5 static-search_panel__item_text static-search_panel__item_title">
              <div className="flex items-center justify-start">
                <div className="mr-2.5">
                  <UsersIcon className="w-6 h-6 opacity-30" />
                </div>
                <div>ご利用人数</div>
              </div>
            </div>
            <div className="static-search_panel__item_input_area">
              <div>
                <PeopleNumberInput
                  value={roomPeoples}
                  maxAdultNum={maxAdultNum}
                  maxChildNum={maxChildNum}
                  maxChildAge={maxChildAge}
                  kidsPolicies={kidsPolicies}
                  onChange={handleRoomPeoplesChange}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      {roomsCount > 0 && !isRoomCancelRequesting ? (
        <RoomPlanSelect
          roomInfos={roomInfos}
          selectedPlanDetail={selectedPlanDetail}
          roomsCount={roomsCount}
          initialScrollEnabled={isSearchConditionModified}
          roomDetailUrl={roomDetailUrl}
          planDetailUrl={planDetailUrl}
          closeImageUrl={closeImageUrl}
          restaurantImageUrl={restaurantImageUrl}
          ageNumsKana={ageNumbsKana}
          hiddenRoomTokens={hiddenRoomTokens}
          selectFirstRoomWithPlanDetail={selectFirstRoomWithPlanDetail}
          selectRoom={selectRoom}
        />
      ) : (<div className="mt-6" style={{color: 'red'}}>{message}</div>)}
      {hotel && hotelNotes && (
        <div
          className="fixed left-0 right-0 z-50"
          style={{ bottom: roomsCount > 1 ? 45 : 0 }}
        >
          <HotelPolicyModalLauncher
            closeImageUrl={closeImageUrl}
            hotel={hotel}
            hotelNotes={hotelNotes}
          />
        </div>
      )}
      {roomsCount > 1 && selectedRoomTypeDetails && selectedPlanDetail && (
        <SelectedRoomsDisplay
          roomsCount={roomsCount}
          roomTypeDetails={selectedRoomTypeDetails}
          roomDetailUrl={roomDetailUrl}
          planDetailUrl={planDetailUrl}
          closeImageUrl={closeImageUrl}
          restaurantImageUrl={restaurantImageUrl}
          ageNumsKana={ageNumbsKana}
          selectedPlanDetail={selectedPlanDetail}
          cancelRoom={cancelRoom}
          bottom={hotel && hotelNotes ? 93 : 45}
        />
      )}
      {(isSubmitting ||
        isCheckinTimeListFetching ||
        isStayTimeListFetching ||
        isSearchDataFetching ||
        isRoomTypesListFetching ||
        isSelectedRoomDataFetching ||
        isFirstRoomWithPlanRegistering ||
        isRoomCancelRequesting) && (
        <div className="fixed top-0 bottom-0 left-0 right-0 flex flex-col items-center justify-center bg-black bg-opacity-50 z-100">
          <Spinner />
        </div>
      )}
      <form
        action={infoInputUrl}
        method="get"
        onSubmit={handleFormSubmit}
        style={roomsCount > 1 ? {} : { visibility: 'hidden' }}
      >
        <div className="z-40 footer_fix_btn">
          <button
            disabled={!allSelected || isSubmitting}
            ref={submitButtonRef}
            type="submit"
            className="rounded-none common_footer_btn"
            style={
              !allSelected || isSubmitting
                ? { pointerEvents: 'none', background: '#f1f1f1' }
                : { pointerEvents: 'auto' }
            }
          >
            予約を進める {'>>'}
          </button>
        </div>
      </form>
    </>
  );
};

export default SearchPanelForm;

const element = document.getElementById(
  'user__booking__search_panel__searchPanelForm'
);
if (element) {
  const {
    stay_search_data_url,
    dayuse_search_data_url,
    max_child_age,
    max_adult_num,
    max_child_num,
    kids_policies: kidsPoliciesJson,
    lp_param,
    close_button_image_url,
    show_dayuse_switch,
    checkin_time_url,
    stay_time_url,
    errors,
    close_image_url,
    plan_detail_url,
    restaurant_image_url,
    plan_room_type_url,
    selected_room_data_url,
    room_detail_url,
    info_input_url,
    selected_room_cancel_url,
    hotel,
    hotel_notes,
  } = element.dataset;
  const kidsPolicies = kidsPoliciesJson ? JSON.parse(kidsPoliciesJson) : [];
  ReactDOM.render(
    <SearchPanelForm
      staySearchDataUrl={stay_search_data_url}
      dayuseSearchDataUrl={dayuse_search_data_url}
      maxChildAge={max_child_age ? parseInt(max_child_age, 10) : 0}
      maxAdultNum={max_adult_num ? parseInt(max_adult_num, 10) : 0}
      maxChildNum={max_child_num ? parseInt(max_child_num, 10) : 0}
      kidsPolicies={kidsPolicies}
      lpParam={lp_param}
      closeButtonImageUrl={close_button_image_url}
      showDayuseSwitch={
        show_dayuse_switch ? Boolean(parseInt(show_dayuse_switch, 10)) : false
      }
      checkinTimeUrl={checkin_time_url}
      stayTimeUrl={stay_time_url}
      errors={errors ? JSON.parse(errors) : undefined}
      closeImageUrl={close_image_url}
      planDetailUrl={plan_detail_url}
      restaurantImageUrl={restaurant_image_url}
      planRoomTypeUrl={plan_room_type_url}
      selectedRoomDataUrl={selected_room_data_url}
      roomDetailUrl={room_detail_url}
      infoInputUrl={info_input_url}
      selectedRoomCancelUrl={selected_room_cancel_url}
      hotel={hotel ? JSON.parse(hotel) : undefined}
      hotelNotes={hotel_notes ? JSON.parse(hotel_notes) : undefined}
    />,
    element
  );
}
