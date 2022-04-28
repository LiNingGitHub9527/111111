/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, {
  FC,
  memo,
  ReactElement,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import Measure from 'react-measure';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import api from '../../../../common/utils/api';
import { DEFAULT_ERROR_MESSAGE } from '../constants';
import Spinner from '../../components/Spinner';
import {
  PlanDetail,
  PlanDetailResponseData,
  Room,
  RoomDetailResponseData,
  RoomInfo,
  RoomTypeDetail,
} from '../../datatypes';
import PlanDetailModal from './PlanDetailModal';
import RoomDetailModal from './RoomDetailModal';

const PLAN_ORDER = {
  PRICE_ASC: 'price-asc',
  PRICE_DESC: 'price-desc',
} as const;

type RoomInfoPlanDetailPair = {
  roomInfo: RoomInfo;
  planDetail: PlanDetail;
  roomTypeDetail: RoomTypeDetail;
};

type PlanOrder = typeof PLAN_ORDER[keyof typeof PLAN_ORDER];

type PlanNameProps = {
  name: string;
  onClick: (event: React.MouseEvent<HTMLAnchorElement>) => void;
};

const PlanNameUnmemorized: FC<PlanNameProps> = ({ name, onClick }) => {
  return (
    <a className="room_menu_main" onClick={onClick}>
      {name}
    </a>
  );
};
const PlanName = memo(PlanNameUnmemorized);

type Props = {
  roomInfos: RoomInfo[] | null;
  selectedPlanDetail: PlanDetail | null;
  roomsCount: number;
  initialScrollEnabled?: boolean;
  roomDetailUrl?: string;
  planDetailUrl?: string;
  closeImageUrl?: string;
  restaurantImageUrl?: string;
  ageNumsKana: string[] | null;
  hiddenRoomTokens: string[][];
  selectFirstRoomWithPlanDetail: (room: Room, planDetail: PlanDetail) => void;
  selectRoom: (index: number, room: Room) => Promise<void>;
};

const RoomPlanSelect: FC<Props> = ({
  roomInfos,
  selectedPlanDetail,
  roomsCount,
  initialScrollEnabled = false,
  roomDetailUrl,
  planDetailUrl,
  closeImageUrl,
  restaurantImageUrl,
  ageNumsKana,
  hiddenRoomTokens,
  selectFirstRoomWithPlanDetail,
  selectRoom,
}) => {
  const csrfToken = useCsrfToken();

  const [roomInfosList, setRoomInfosList] = useState<(RoomInfo[] | null)[]>(
    new Array(roomsCount).fill(null)
  );
  const [planOrders, setPlanOrders] = useState<(PlanOrder | null)[] | null>(
    null
  );

  const planOrdersInitializedRef = useRef<boolean>(false);
  useEffect(() => {
    if (!planOrdersInitializedRef.current && roomInfos) {
      planOrdersInitializedRef.current = true;
      setPlanOrders(new Array(roomInfos.length).fill(null));
    }
  }, [roomInfos]);

  const [roomDetail, setRoomDetail] = useState<RoomTypeDetail | null>(null);
  const [roomInfoPlanDetailPair, setRoomInfoPlanDetailPair] =
    useState<RoomInfoPlanDetailPair | null>(null);

  const [isRoomDetailFetching, setIsRoomDetailFetching] =
    useState<boolean>(false);
  const [isPlanDetailFetching, setIsPlanDetailFetching] =
    useState<boolean>(false);

  const [containerHeight, setContainerHeight] = useState<number>(0);

  const rootRef = useRef<HTMLDivElement | null>(null);
  const isScrolledRef = useRef<boolean>(!initialScrollEnabled);

  const currentSelectIndexRef = useRef<number>(-1);
  const currentSelectIndex = roomInfos?.[0]?.currentSelectIndex ?? -1;

  useEffect(() => {
    if (
      currentSelectIndex < 0 ||
      currentSelectIndex === currentSelectIndexRef.current
    ) {
      return;
    }

    if (currentSelectIndex > 0) {
      isScrolledRef.current = false;
    }
    currentSelectIndexRef.current = currentSelectIndex;
    setRoomInfosList([
      ...roomInfosList.slice(0, currentSelectIndex),
      roomInfos,
      ...roomInfosList.slice(currentSelectIndex + 1),
    ]);
  }, [currentSelectIndex, roomInfos, roomInfosList]);

  const createPlanSelectButtonClickHandler = useCallback(
    (index: number, room: Room, planDetail: PlanDetail) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (selectedPlanDetail) {
          selectRoom(index, room);
        } else {
          selectFirstRoomWithPlanDetail(room, planDetail);
        }
      },
    [selectFirstRoomWithPlanDetail, selectRoom, selectedPlanDetail]
  );

  const createPlanOrderChangeHandler = useCallback(
    (index: number, order: PlanOrder) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (!planOrders || order === planOrders[index]) {
          return;
        }

        setPlanOrders([
          ...planOrders.slice(0, index),
          order,
          ...planOrders.slice(index + 1),
        ]);
      },
    [planOrders]
  );

  const createRoomDetailFetchHandler = useCallback(
    (index: number, roomToken?: string) =>
      async (_event?: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (
          !roomDetailUrl ||
          !csrfToken ||
          !roomToken ||
          index !== currentSelectIndex
        ) {
          return;
        }

        setIsRoomDetailFetching(true);
        try {
          const data = (await api.post(roomDetailUrl, {
            room_token: roomToken,
            _token: csrfToken,
          })) as RoomDetailResponseData;
          if (data.res === 'ok') {
            setRoomDetail(data.roomTypeDetail);
          } else {
            alert(data.message);
          }
        } catch (_error) {
          alert(DEFAULT_ERROR_MESSAGE);
        } finally {
          setIsRoomDetailFetching(false);
        }
      },
    [csrfToken, currentSelectIndex, roomDetailUrl]
  );

  const closeRoomDetail = useCallback((): void => {
    setRoomDetail(null);
  }, []);

  const createPlanDetailOpenHandler = useCallback(
    (roomInfo: RoomInfo, planDetail: PlanDetail, roomToken?: string) =>
      async (_event?: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (
          !roomDetailUrl ||
          !planDetailUrl ||
          !csrfToken ||
          !planDetail.plan_token ||
          !roomToken
        ) {
          return;
        }

        setIsPlanDetailFetching(true);
        try {
          const data = (await api.post(planDetailUrl, {
            plan_token: planDetail.plan_token,
            _token: csrfToken,
          })) as PlanDetailResponseData;

          const roomData = (await api.post(roomDetailUrl, {
            room_token: roomToken,
            _token: csrfToken,
          })) as RoomDetailResponseData;

          if (data.res === 'ok' && roomData.res === 'ok') {
            setRoomInfoPlanDetailPair({
              roomInfo,
              planDetail: data.plan_detail,
              roomTypeDetail: roomData.roomTypeDetail
            });
          } else {
            alert(data.message);
          }
        } catch (_erro) {
          alert(DEFAULT_ERROR_MESSAGE);
        } finally {
          setIsPlanDetailFetching(false);
        }
      },
    [csrfToken, planDetailUrl, roomDetailUrl]
  );

  const closePlanDetail = useCallback((): void => {
    setRoomInfoPlanDetailPair(null);
  }, []);

  const selectPlanFromModal = useCallback((): void => {
    if (!roomInfoPlanDetailPair || currentSelectIndex < 0) {
      return;
    }

    const handler = createPlanSelectButtonClickHandler(
      currentSelectIndex,
      roomInfoPlanDetailPair.roomInfo.room,
      roomInfoPlanDetailPair.planDetail
    );
    handler();
  }, [
    createPlanSelectButtonClickHandler,
    currentSelectIndex,
    roomInfoPlanDetailPair,
  ]);

  useEffect(() => {
    if (currentSelectIndex < 0) {
      setContainerHeight(0);
    } else {
      const roomInfoSelects = document.getElementsByClassName('roomInfoSelect');
      const currentRoomInfoSelect = roomInfoSelects[currentSelectIndex];
      const height = currentRoomInfoSelect.clientHeight;
      if (height) {
        setContainerHeight(height);
      }
    }
  }, [currentSelectIndex]);

  const scrollToTop = useCallback((): void => {
    const top = rootRef.current?.offsetTop;
    if (top) {
      window.scrollTo({
        top,
        left: 0,
        behavior: 'smooth',
      });
    }
  }, []);

  useEffect(() => {
    if (!isScrolledRef.current && containerHeight > 0 && roomInfos) {
      isScrolledRef.current = true;
      setTimeout(() => {
        scrollToTop();
        setPlanOrders(new Array(roomInfos.length).fill(null));
      }, 200);
    }
  }, [containerHeight, roomInfos, scrollToTop]);

  const roomInfosListOrdered = useMemo((): (RoomInfo[] | null)[] => {
    if (currentSelectIndex < 0) {
      return roomInfosList;
    }

    const currentRoomInfos = roomInfosList[currentSelectIndex];
    if (!currentRoomInfos) {
      return roomInfosList;
    }

    const currentRoomInfosOrdered = currentRoomInfos
      .filter(
        (info: RoomInfo) =>
          info.room.room_token &&
          !hiddenRoomTokens[currentSelectIndex].includes(info.room.room_token)
      )
      .map((info: RoomInfo, i: number) => {
        const order = planOrders?.[i];
        if (!order) {
          return info;
        }

        const planPrices = info.planDetails.map(
          (detail: PlanDetail, i: number) => ({
            i,
            price: info.amounts[detail.plan_token ?? ''],
          })
        );
        planPrices.sort(
          (a, b) =>
            (order === PLAN_ORDER.PRICE_DESC ? -1 : 1) * (a.price - b.price)
        );

        return {
          ...info,
          planDetails: planPrices.map((element) => info.planDetails[element.i]),
        };
      });

    return [
      ...roomInfosList.slice(0, currentSelectIndex),
      currentRoomInfosOrdered,
      ...roomInfosList.slice(currentSelectIndex + 1),
    ];
  }, [currentSelectIndex, roomInfosList, hiddenRoomTokens, planOrders]);

  return (
    <>
      <div ref={rootRef} className={roomsCount > 1 ? 'mb-52' : 'mb-0'}>
        {currentSelectIndex === 0 && roomsCount === 1 && (
          <div className="mt-6 text-base">
            お部屋
            {ageNumsKana?.[0] ? `（${ageNumsKana[0]}）` : ''}
            をご選択ください
          </div>
        )}
        {currentSelectIndex >= 0 && roomsCount > 1 && (
          <div className="mt-6 text-base">
            {currentSelectIndex + 1}部屋目
            {ageNumsKana?.[currentSelectIndex]
              ? `（${ageNumsKana[currentSelectIndex]}）`
              : ''}
            をご選択ください
          </div>
        )}
        {currentSelectIndex < 0 && selectedPlanDetail && (
          <div className="mt-6 text-base">
            {roomsCount > 1 ? '全ての' : ''}お部屋を選択済みです
          </div>
        )}
        <div
          className="relative w-full overflow-hidden listContainer"
          style={{ height: containerHeight }}
        >
          {[...Array(roomsCount).keys()].map((roomSelectIndex: number) => (
            <Measure
              key={`room-select-${roomSelectIndex}`}
              bounds
              onResize={(contentRect): void => {
                const height = contentRect.bounds?.height;
                if (height != null && roomSelectIndex === currentSelectIndex) {
                  setContainerHeight(height);
                }
              }}
            >
              {({ measureRef }): ReactElement => (
                <div
                  ref={measureRef}
                  className={[
                    'roomInfoSelect',
                    'absolute w-full',
                    currentSelectIndex < 0 &&
                      !selectedPlanDetail &&
                      'listBeforeShown',
                    currentSelectIndex >= 0 &&
                      roomSelectIndex > currentSelectIndex &&
                      'listBeforeShown',
                    currentSelectIndex >= 0 &&
                      roomSelectIndex === currentSelectIndex &&
                      'listShown',
                    currentSelectIndex >= 0 &&
                      roomSelectIndex < currentSelectIndex &&
                      'listHidden',
                    currentSelectIndex < 0 &&
                      selectedPlanDetail &&
                      'listHidden',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                >
                  {(roomInfosListOrdered[roomSelectIndex] ?? []).map(
                    (info: RoomInfo, roomIndex: number) => (
                      <React.Fragment
                        key={`select-${roomSelectIndex}-${roomIndex}`}
                      >
                        {roomIndex !== 0 && (
                          <div className="w-full bg-gray-200 h-0.5" />
                        )}
                        <div key={`room-${roomIndex}`}>
                          <div
                            className="my-6 room_type_wrap"
                            style={roomIndex === 0 ? { marginTop: 4 } : {}}
                          >
                            <div className="relative w-full h-0 rounded-t pb-9/26 sm:pb-7/10">
                              <img
                                className="absolute top-0 left-0 object-cover w-full h-full rounded-t cursor-pointer"
                                src={info.room.images[0]}
                                alt=""
                                onClick={createRoomDetailFetchHandler(
                                  roomSelectIndex,
                                  info.room.room_token
                                )}
                              />
                              <div className="absolute top-0 left-0 px-4 py-2 bg-black opacity-70">
                                <p
                                  className="mb-0 text-lg text-white room_menu_main"
                                  onClick={createRoomDetailFetchHandler(
                                    roomSelectIndex,
                                    info.room.room_token
                                  )}
                                >
                                  {info.room.name + '　'}
                                  {info.room.bed_sum > 0 &&
                                    info.room.bed_sum + 'ベッド'}
                                </p>
                              </div>
                            </div>
                            <div className="p-2 sm:py-3 room_type_order">
                              <p className="text-sm order_text">
                                このお部屋のプラン
                              </p>
                              {currentSelectIndex === 0 &&
                                info.planDetails.length > 1 && (
                                  <div
                                    className="order_block"
                                    style={{ width: 138 }}
                                  >
                                    <button
                                      className="w-16 h-6 text-sm order_btn"
                                      style={
                                        planOrders &&
                                        planOrders[roomIndex] ===
                                          PLAN_ORDER.PRICE_ASC
                                          ? {
                                              backgroundColor:
                                                'rgb(33, 133, 208)',
                                              color: 'rgb(255, 255, 255)',
                                            }
                                          : {
                                              backgroundColor:
                                                'rgb(239, 239, 239)',
                                              color: 'rgb(118, 118, 118)',
                                            }
                                      }
                                      onClick={createPlanOrderChangeHandler(
                                        roomIndex,
                                        PLAN_ORDER.PRICE_ASC
                                      )}
                                    >
                                      安い順
                                    </button>
                                    <button
                                      className="w-16 h-6 text-sm order_btn"
                                      style={
                                        planOrders &&
                                        planOrders[roomIndex] ===
                                          PLAN_ORDER.PRICE_DESC
                                          ? {
                                              backgroundColor:
                                                'rgb(33, 133, 208)',
                                              color: 'rgb(255, 255, 255)',
                                            }
                                          : {
                                              backgroundColor:
                                                'rgb(239, 239, 239)',
                                              color: 'rgb(118, 118, 118)',
                                            }
                                      }
                                      onClick={createPlanOrderChangeHandler(
                                        roomIndex,
                                        PLAN_ORDER.PRICE_DESC
                                      )}
                                    >
                                      高い順
                                    </button>
                                  </div>
                                )}
                            </div>
                            <div>
                              {info.planDetails.map(
                                (planDetail: PlanDetail, planIndex: number) => (
                                  <div
                                    key={`room-plan-${planIndex}`}
                                    className="p-0 room_menu_block"
                                  >
                                    <div>
                                      <div className="relative w-32 h-0 pb-7/10">
                                        <img
                                          className="absolute top-0 left-0 object-cover w-full h-full"
                                          src={planDetail.cover_image}
                                          alt=""
                                        />
                                        <div className="absolute bottom-0 right-0 px-2 text-base text-white bg-black h-7 opacity-70">
                                          ¥
                                          {info.amounts[
                                            planDetail.plan_token ?? ''
                                          ].toLocaleString()}
                                        </div>
                                      </div>
                                    </div>
                                    <div className="flex flex-col items-start justify-start w-full px-2 py-1 sm:px-3 sm:py-3 room_menu_contents">
                                      <PlanName
                                        name={
                                          planDetail.name +
                                          (planDetail.is_meal
                                            ? '　' +
                                              `${planDetail.meal_types.length}食付き`
                                            : '')
                                        }
                                        onClick={createPlanDetailOpenHandler(
                                          info,
                                          planDetail,
                                          info.room.room_token
                                        )}
                                      />
                                      <div className="w-full">
                                        <button
                                          disabled={
                                            currentSelectIndex < 0 ||
                                            roomSelectIndex !==
                                              currentSelectIndex
                                          }
                                          className={[
                                            'w-full room_menu_btn_choose plan_choose_btn',
                                            (currentSelectIndex < 0 ||
                                              roomSelectIndex !==
                                                currentSelectIndex) &&
                                              'bg-gray-300 cursor-default',
                                          ]
                                            .filter(Boolean)
                                            .join(' ')}
                                          style={{ padding: '1px 6px' }}
                                          onClick={createPlanSelectButtonClickHandler(
                                            roomSelectIndex,
                                            info.room,
                                            planDetail
                                          )}
                                        >
                                          {roomsCount > 1
                                            ? '選択する'
                                            : '予約する'}
                                        </button>
                                      </div>
                                    </div>
                                  </div>
                                )
                              )}
                            </div>
                          </div>
                        </div>
                      </React.Fragment>
                    )
                  )}
                </div>
              )}
            </Measure>
          ))}
        </div>
      </div>
      {roomDetail && (
        <RoomDetailModal
          roomSelectIndex={currentSelectIndex}
          roomTypeDetail={roomDetail}
          closeImageUrl={closeImageUrl}
          ageNumsKana={ageNumsKana}
          close={closeRoomDetail}
        />
      )}
      {roomInfoPlanDetailPair?.planDetail && (
        <PlanDetailModal
          price={
            roomInfoPlanDetailPair.roomInfo.amounts[
              roomInfoPlanDetailPair.planDetail.plan_token ?? ''
            ]
          }
          planDetail={roomInfoPlanDetailPair.planDetail}
          closeImageUrl={closeImageUrl}
          restaurantImageUrl={restaurantImageUrl}
          selectPlan={selectPlanFromModal}
          close={closePlanDetail}
          roomTypeDetail={roomInfoPlanDetailPair.roomTypeDetail}
        />
      )}
      {(isRoomDetailFetching || isPlanDetailFetching) && (
        <div className="fixed top-0 bottom-0 left-0 right-0 flex flex-col items-center justify-center bg-black bg-opacity-50 z-100">
          <Spinner />
        </div>
      )}
    </>
  );
};

export default RoomPlanSelect;
