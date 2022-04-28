/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useEffect, useMemo, useState } from 'react';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import { useVh } from '../../../../common/hooks/use-vh';
import api from '../../../../common/utils/api';
import {
  Room,
  RoomDetailResponseData,
  RoomTypeDetail,
  RoomTypes,
  SelectedRoomCancelResponseData,
  SelectedRoomDataResponseData,
} from '../../datatypes';
import CancelButton from './CancelButton';
import RoomDetailDisplay from './RoomDetailDisplay';
import SelectedRoomDisplay from './SelectedRoomDisplay';

const ROOM_ORDER = {
  PRICE_ASC: 'price-asc',
  PRICE_DESC: 'price-desc',
} as const;

type RoomOrder = typeof ROOM_ORDER[keyof typeof ROOM_ORDER];

type Props = {
  roomTypes: RoomTypes;
  closeImageUrl?: string;
  selectedRoomDataUrl?: string;
  roomDetailUrl?: string;
  restaurantImageUrl?: string;
  changeAllSelected: (selected: boolean) => void;
  selectedRoomCancelUrl?: string;
};

const RoomSelect: FC<Props> = ({
  roomTypes: { ageNumsKana, stayAbleRooms, targetPlan },
  closeImageUrl,
  selectedRoomDataUrl,
  roomDetailUrl,
  restaurantImageUrl,
  changeAllSelected,
  selectedRoomCancelUrl,
}) => {
  const [selectedRooms, setSelectedRooms] = useState<(Room | null)[]>(
    new Array(stayAbleRooms.length).fill(null)
  );
  const [selectedRoomDataResponses, setSelectedRoomDataResponses] = useState<
    (SelectedRoomDataResponseData | null)[]
  >(new Array(stayAbleRooms.length).fill(null));
  const [showAll, setShowAll] = useState<boolean>(false);
  const [selectedRoomTypeDetails, setSelectedRoomTypeDetails] = useState<
    (RoomTypeDetail | null)[]
  >(new Array(stayAbleRooms.length).fill(null));
  const [roomOrders, setRoomOrders] = useState<(RoomOrder | null)[]>(
    new Array(stayAbleRooms.length).fill(null)
  );

  const csrfToken = useCsrfToken();

  const resetRoomSelection = useCallback(
    (index: number): void => {
      setSelectedRooms([
        ...selectedRooms.slice(0, index),
        null,
        ...selectedRooms.slice(index + 1),
      ]);
      setSelectedRoomDataResponses([
        ...selectedRoomDataResponses.slice(0, index),
        null,
        ...selectedRoomDataResponses.slice(index + 1),
      ]);
    },
    [selectedRoomDataResponses, selectedRooms]
  );

  const fetchSelectedRoomData = useCallback(
    async (index: number, roomToken?: string): Promise<void> => {
      if (!selectedRoomDataUrl || !csrfToken || !roomToken) {
        return;
      }

      const data = (await api.post(selectedRoomDataUrl, {
        room_token: roomToken,
        _token: csrfToken,
      })) as SelectedRoomDataResponseData;
      if (data.res === 'ok') {
        setSelectedRoomDataResponses([
          ...selectedRoomDataResponses.slice(0, index),
          data,
          ...selectedRoomDataResponses.slice(index + 1),
        ]);
      } else {
        resetRoomSelection(index);
        alert(data.message);
      }
    },
    [
      selectedRoomDataUrl,
      csrfToken,
      selectedRoomDataResponses,
      resetRoomSelection,
    ]
  );

  const createRoomSelectButtonClickHandler = useCallback(
    (index: number, room: Room) =>
      async (_event?: React.MouseEvent<HTMLButtonElement>): Promise<void> => {
        setSelectedRooms([
          ...selectedRooms.slice(0, index),
          room,
          ...selectedRooms.slice(index + 1),
        ]);
        await fetchSelectedRoomData(index, room?.room_token);
      },
    [fetchSelectedRoomData, selectedRooms]
  );

  const createCancelButtonClickHandler = useCallback(
    (index: number) =>
      async (_event: React.MouseEvent<HTMLButtonElement>): Promise<void> => {
        const roomToken = selectedRooms[index]?.room_token;
        if (!selectedRoomCancelUrl || !csrfToken || !roomToken) {
          return;
        }

        const data = (await api.post(selectedRoomCancelUrl, {
          room_token: roomToken,
          room_num: index,
          _token: csrfToken,
        })) as SelectedRoomCancelResponseData;
        if (data.res === 'ok') {
          setSelectedRooms([
            ...selectedRooms.slice(0, index),
            null,
            ...selectedRooms.slice(index + 1),
          ]);

          const shownRoomTokens = data.showRoomTokens;
          const roomDataResponses = selectedRoomDataResponses.map(
            (
              response: SelectedRoomDataResponseData | null,
              i: number
            ): SelectedRoomDataResponseData | null => {
              if (i === index || !response) {
                return null;
              }

              return {
                ...response,
                hideRoomTokens: response.hideRoomTokens.filter(
                  (token: string) => !shownRoomTokens.includes(token)
                ),
              };
            }
          );
          setSelectedRoomDataResponses(roomDataResponses);
        } else {
          alert(data.message);
        }
      },
    [csrfToken, selectedRoomCancelUrl, selectedRoomDataResponses, selectedRooms]
  );

  const handleShowAllClick = useCallback(
    (_event: React.MouseEvent<HTMLDivElement>): void => {
      setShowAll(true);
    },
    []
  );

  const createRoomDetailFetchHandler = useCallback(
    (index: number, roomToken?: string) =>
      async (_event?: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (!roomDetailUrl || !csrfToken || !roomToken) {
          return;
        }

        const data = (await api.post(roomDetailUrl, {
          room_token: roomToken,
          _token: csrfToken,
        })) as RoomDetailResponseData;
        if (data.res === 'ok') {
          setSelectedRoomTypeDetails([
            ...selectedRoomTypeDetails.slice(0, index),
            data.roomTypeDetail,
            ...selectedRoomTypeDetails.slice(index + 1),
          ]);
        } else {
          alert(data.message);
        }
      },
    [csrfToken, roomDetailUrl, selectedRoomTypeDetails]
  );

  const createRoomDetailFetchHandlerFromSelectedRoomDisplay = useCallback(
    (index: number, roomToken?: string) => async (): Promise<void> => {
      const fetchHandler = createRoomDetailFetchHandler(index, roomToken);
      await fetchHandler();
    },
    [createRoomDetailFetchHandler]
  );

  const createSelectedRoomDataFetchHandlerFromRoomDetailDisplay = useCallback(
    (index: number, roomToken?: string) => async (): Promise<void> => {
      const fetchedRoomToken =
        selectedRoomDataResponses[index]?.selectedRoomData?.roomToken;
      if (!fetchedRoomToken || roomToken !== fetchedRoomToken) {
        const room = stayAbleRooms[index].find(
          (r: Room) => r.room_token === roomToken
        );
        if (!room?.room_token) {
          return;
        }

        const selectRoom = createRoomSelectButtonClickHandler(index, room);
        await selectRoom();
      }
      setSelectedRoomTypeDetails([
        ...selectedRoomTypeDetails.slice(0, index),
        null,
        ...selectedRoomTypeDetails.slice(index + 1),
      ]);
    },
    [
      createRoomSelectButtonClickHandler,
      selectedRoomDataResponses,
      selectedRoomTypeDetails,
      stayAbleRooms,
    ]
  );

  const createRoomDetailCloseButtonClickHandler = useCallback(
    (index: number) =>
      (_event: React.MouseEvent<HTMLButtonElement>): void => {
        setSelectedRoomTypeDetails([
          ...selectedRoomTypeDetails.slice(0, index),
          null,
          ...selectedRoomTypeDetails.slice(index + 1),
        ]);
      },
    [selectedRoomTypeDetails]
  );

  const hiddenRoomTokens = useMemo(
    (): string[][] =>
      [...Array(stayAbleRooms.length).keys()].map((index: number): string[] =>
        [
          ...selectedRoomDataResponses.slice(0, index),
          null,
          ...selectedRoomDataResponses.slice(index + 1),
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
      ),
    [selectedRoomDataResponses, stayAbleRooms.length]
  );

  const allSelected =
    selectedRoomDataResponses.every(
      (data: SelectedRoomDataResponseData | null) => data != null
    ) &&
    selectedRoomDataResponses.some(
      (data: SelectedRoomDataResponseData | null) => data?.is_all_selected
    );

  useEffect(() => {
    changeAllSelected(allSelected);
  }, [allSelected, changeAllSelected]);

  const createRoomOrderChangeHandler = useCallback(
    (index: number, order: RoomOrder) =>
      (_event: React.MouseEvent<HTMLButtonElement>): void => {
        if (order === roomOrders[index]) {
          return;
        }

        setRoomOrders([
          ...roomOrders.slice(0, index),
          order,
          ...roomOrders.slice(index + 1),
        ]);
      },
    [roomOrders]
  );

  const stayAbleRoomsOrdered = useMemo(
    (): Room[][] =>
      stayAbleRooms.map((rooms: Room[], index: number) => {
        const order = roomOrders[index];
        if (!order) {
          return rooms;
        }

        const roomPrices = rooms.map((room: Room, i: number) => ({
          i,
          price: room.amount,
        }));
        roomPrices.sort(
          (a, b) =>
            (order === ROOM_ORDER.PRICE_DESC ? -1 : 1) * (a.price - b.price)
        );

        return roomPrices.map((element) => rooms[element.i]);
      }),
    [roomOrders, stayAbleRooms]
  );

  const vh = useVh();

  return (
    <>
      {stayAbleRoomsOrdered.map((rooms: Room[], index: number) => (
        <div key={`rooms-${index}`} className="room_type_wrap">
          <div className="room_type_title">
            <p className="room_type_text">
              {index + 1}部屋目（{ageNumsKana[index]}）
            </p>
            {selectedRoomTypeDetails[index] && (
              <button onClick={createRoomDetailCloseButtonClickHandler(index)}>
                {closeImageUrl ? (
                  <img
                    src={closeImageUrl}
                    alt=""
                    className="detail_cancel_btn"
                  />
                ) : (
                  <span className="font-bold">×</span>
                )}
              </button>
            )}
            {selectedRoomDataResponses[index] &&
              !selectedRoomTypeDetails[index] && (
                <CancelButton onClick={createCancelButtonClickHandler(index)} />
              )}
          </div>
          {selectedRoomDataResponses[index] &&
            !selectedRoomTypeDetails[index] && (
              <SelectedRoomDisplay
                selectedRoomData={
                  (
                    selectedRoomDataResponses[
                      index
                    ] as SelectedRoomDataResponseData
                  ).selectedRoomData
                }
                fetchRoomDetail={createRoomDetailFetchHandlerFromSelectedRoomDisplay(
                  index,
                  selectedRoomDataResponses[index]?.selectedRoomData?.roomToken
                )}
              />
            )}
          {selectedRoomTypeDetails[index] && (
            <RoomDetailDisplay
              roomTypeDetail={selectedRoomTypeDetails[index] as RoomTypeDetail}
              restaurantImageUrl={restaurantImageUrl}
              fetchSelectedRoomData={createSelectedRoomDataFetchHandlerFromRoomDetailDisplay(
                index,
                selectedRoomTypeDetails[index]?.roomToken
              )}
            />
          )}
          {!selectedRoomDataResponses[index] &&
            !selectedRoomTypeDetails[index] && (
              <>
                <div className="room_type_order">
                  <p className="order_text">金額の並び替え</p>
                  <div className="order_block">
                    <button
                      className="order_btn"
                      style={
                        roomOrders[index] === ROOM_ORDER.PRICE_ASC
                          ? {
                              backgroundColor: 'rgb(33, 133, 208)',
                              color: 'rgb(255, 255, 255)',
                            }
                          : {
                              backgroundColor: 'rgb(239, 239, 239)',
                              color: 'rgb(118, 118, 118)',
                            }
                      }
                      onClick={createRoomOrderChangeHandler(
                        index,
                        ROOM_ORDER.PRICE_ASC
                      )}
                    >
                      安い順
                    </button>
                    <button
                      className="order_btn"
                      style={
                        roomOrders[index] === ROOM_ORDER.PRICE_DESC
                          ? {
                              backgroundColor: 'rgb(33, 133, 208)',
                              color: 'rgb(255, 255, 255)',
                            }
                          : {
                              backgroundColor: 'rgb(239, 239, 239)',
                              color: 'rgb(118, 118, 118)',
                            }
                      }
                      onClick={createRoomOrderChangeHandler(
                        index,
                        ROOM_ORDER.PRICE_DESC
                      )}
                    >
                      高い順
                    </button>
                  </div>
                </div>
                <div
                  className="overflow-scroll"
                  style={{
                    maxHeight: showAll ? `${75 * vh}px` : `${70 * vh}px`,
                  }}
                >
                  {rooms.map((room: Room, i: number) => (
                    <div
                      key={room.room_type_id}
                      className={[
                        'room_menu_block',
                        ((!showAll && i >= 10) ||
                          (room.room_token &&
                            hiddenRoomTokens[index].includes(
                              room.room_token
                            ))) &&
                          'hidden',
                      ]
                        .filter(Boolean)
                        .join(' ')}
                    >
                      <div className="room_menu_img">
                        <img src={room.images[0]} alt="" />
                      </div>
                      <div className="room_menu_contents">
                        <p
                          className="room_menu_main"
                          onClick={createRoomDetailFetchHandler(
                            index,
                            room.room_token
                          )}
                        >
                          {room.name + '　'}
                          {room.bed_sum > 0 && room.bed_sum + 'ベッド / '}
                          {Boolean(targetPlan.is_meal) &&
                            targetPlan.meal_types.length + '食付き'}
                        </p>
                        <div className="room_menu_btn">
                          <button
                            className="room_menu_btn_price"
                            style={{ backgroundColor: 'rgb(239, 239, 239)' }}
                          >
                            ¥{room.amount.toLocaleString()}
                          </button>
                          <button
                            className="room_menu_btn_choose"
                            onClick={createRoomSelectButtonClickHandler(
                              index,
                              room
                            )}
                          >
                            この部屋を選択
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                {!showAll && rooms.length > 10 ? (
                  <div
                    className="cursor-pointer room_type_footer"
                    onClick={handleShowAllClick}
                  >
                    <p>残りのお部屋を見る - {rooms.length - 10}件</p>
                  </div>
                ) : (
                  <div className="room_type_footer">
                    <p>全てのお部屋が表示されています</p>
                  </div>
                )}
              </>
            )}
        </div>
      ))}
    </>
  );
};

export default RoomSelect;
