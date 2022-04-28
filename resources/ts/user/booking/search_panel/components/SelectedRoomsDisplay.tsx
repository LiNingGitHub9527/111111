/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC, useCallback, useMemo, useState } from 'react';
import { XIcon } from '@heroicons/react/outline';
import {
  PlanDetail,
  PlanDetailResponseData,
  RoomDetailResponseData,
  RoomTypeDetail,
} from '../../datatypes';
import PlanDetailModal from './PlanDetailModal';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import api from '../../../../common/utils/api';
import { DEFAULT_ERROR_MESSAGE } from '../constants';
import RoomDetailModal from './RoomDetailModal';
import Spinner from '../../components/Spinner';

type Props = {
  roomsCount: number;
  roomTypeDetails: (RoomTypeDetail | null)[];
  roomDetailUrl?: string;
  planDetailUrl?: string;
  closeImageUrl?: string;
  restaurantImageUrl?: string;
  ageNumsKana: string[] | null;
  selectedPlanDetail: PlanDetail;
  cancelRoom: (index: number) => Promise<void>;
  bottom: number;
};

const SelectedRoomsDisplay: FC<Props> = ({
  roomsCount,
  roomTypeDetails,
  roomDetailUrl,
  planDetailUrl,
  closeImageUrl,
  restaurantImageUrl,
  ageNumsKana,
  selectedPlanDetail,
  cancelRoom,
  bottom,
}) => {
  const [roomTypeDetail, setRoomTypeDetail] = useState<RoomTypeDetail | null>(
    null
  );
  const [planDetail, setPlanDetail] = useState<PlanDetail | null>(null);
  const [isRoomTypeDetailOpen, setIsRoomTypeDetailOpen] =
    useState<boolean>(false);
  const [isPlanDetailOpen, setIsPlanDetailOpen] = useState<boolean>(false);
  const [isRoomDetailFetching, setIsRoomDetailFetching] =
    useState<boolean>(false);
  const [isPlanDetailFetching, setIsPlanDetailFetching] =
    useState<boolean>(false);

  const csrfToken = useCsrfToken();

  const details = useMemo(
    (): RoomTypeDetail[] =>
      roomTypeDetails.filter(
        (detail: RoomTypeDetail | null) => detail != null
      ) as RoomTypeDetail[],
    [roomTypeDetails]
  );

  const createRoomTypeDetailOpenHandler = useCallback(
    (detail: RoomTypeDetail) =>
      async (_event?: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (!roomDetailUrl || !csrfToken || !detail.roomToken) {
          return;
        }

        setIsRoomDetailFetching(true);
        try {
          const data = (await api.post(roomDetailUrl, {
            room_token: detail.roomToken,
            _token: csrfToken,
          })) as RoomDetailResponseData;
          if (data.res === 'ok') {
            setRoomTypeDetail(data.roomTypeDetail);
            setIsRoomTypeDetailOpen(true);
          } else {
            alert(data.message);
            setRoomTypeDetail(null);
            setIsRoomTypeDetailOpen(false);
          }
        } catch (_error) {
          alert(DEFAULT_ERROR_MESSAGE);
        } finally {
          setIsRoomDetailFetching(false);
        }
      },
    [csrfToken, roomDetailUrl]
  );

  const closeRoomTypeDetail = useCallback((): void => {
    setRoomTypeDetail(null);
    setIsRoomTypeDetailOpen(false);
  }, []);

  const handleRoomCancel = useCallback((): void => {
    if (roomTypeDetail?.roomNum == null) {
      return;
    }

    cancelRoom(roomTypeDetail.roomNum);
  }, [cancelRoom, roomTypeDetail]);

  const createRoomCancelHandler = useCallback(
    (detail: RoomTypeDetail) =>
      (_event?: React.MouseEvent<SVGSVGElement>): void => {
        cancelRoom(detail.roomNum);
      },
    [cancelRoom]
  );

  const createPlanDetailOpenHandler = useCallback(
    (detail: RoomTypeDetail) =>
      async (_event?: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (!planDetailUrl || !csrfToken || !selectedPlanDetail.plan_token) {
          return;
        }

        setIsPlanDetailFetching(true);
        try {
          const data = (await api.post(planDetailUrl, {
            plan_token: selectedPlanDetail.plan_token,
            _token: csrfToken,
          })) as PlanDetailResponseData;
          if (data.res === 'ok') {
            setPlanDetail(data.plan_detail);
            setRoomTypeDetail(detail);
            setIsPlanDetailOpen(true);
          } else {
            alert(data.message);
            setPlanDetail(null);
            setRoomTypeDetail(null);
            setIsPlanDetailOpen(false);
          }
        } catch (_error) {
          alert(DEFAULT_ERROR_MESSAGE);
        } finally {
          setIsPlanDetailFetching(false);
        }
      },
    [csrfToken, planDetailUrl, selectedPlanDetail.plan_token]
  );

  const closePlanDetail = useCallback((): void => {
    setRoomTypeDetail(null);
    setIsPlanDetailOpen(false);
  }, []);

  return (
    <>
      <div
        className="fixed left-0 z-10 w-full overflow-y-scroll bg-white max-h-40 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4"
        style={{ bottom }}
      >
        {details.map((detail: RoomTypeDetail) => (
          <div key={detail.roomToken} className="relative w-full shadow">
            <div className="flex justify-start w-full">
              <div>
                <div className="relative w-24 h-0 pb-7/10">
                  <img
                    className="absolute top-0 left-0 object-cover w-full h-full"
                    src={detail.roomDetail.images[0]}
                    alt=""
                  />
                  <div className="absolute bottom-0 right-0 h-5 px-1 text-sm text-white bg-black opacity-70">
                    ¥{detail.roomDetail.amount.toLocaleString()}
                  </div>
                </div>
              </div>
              <div
                className="flex justify-between"
                style={{ width: 'calc(100% - 96px)' }}
              >
                <div
                  className="flex flex-col items-start justify-start py-1 pl-1.5"
                  style={{ width: 'calc(100% - 28px)' }}
                >
                  <div className="w-full">
                    <div className="w-full overflow-hidden overflow-ellipsis whitespace-nowrap">
                      <a
                        className="text-sm room_menu_main"
                        onClick={createRoomTypeDetailOpenHandler(detail)}
                      >
                        {detail.roomDetail.name + '　'}
                        {detail.roomDetail.bed_sum > 0 &&
                          detail.roomDetail.bed_sum + 'ベッド'}
                      </a>
                    </div>
                  </div>
                  <div className="w-full mt-1.5">
                    <div className="w-full overflow-hidden overflow-ellipsis whitespace-nowrap">
                      <a
                        className="text-xs room_menu_main"
                        onClick={createPlanDetailOpenHandler(detail)}
                      >
                        {selectedPlanDetail.name +
                          (selectedPlanDetail.is_meal
                            ? '　' +
                              `${selectedPlanDetail.meal_types.length}食付き`
                            : '')}
                      </a>
                    </div>
                  </div>
                </div>
                <div className="flex flex-col items-center justify-center w-7">
                  <XIcon
                    className="w-6 h-6 cursor-pointer"
                    onClick={createRoomCancelHandler(detail)}
                  />
                </div>
              </div>
            </div>
            {roomsCount > 1 && (
              <div className="absolute flex items-center justify-center w-5 h-5 text-white bg-black rounded-full top-0.5 left-0.5 opacity-70">
                <div className="text-sm text-white leading-3 mb-0.5">
                  {detail.roomNum + 1}
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
      {roomTypeDetail?.roomDetail && isRoomTypeDetailOpen && (
        <RoomDetailModal
          isSelected
          roomSelectIndex={roomTypeDetail.roomNum}
          roomTypeDetail={roomTypeDetail}
          closeImageUrl={closeImageUrl}
          ageNumsKana={ageNumsKana}
          close={closeRoomTypeDetail}
          cancel={handleRoomCancel}
        />
      )}
      {planDetail && roomTypeDetail?.roomDetail && isPlanDetailOpen && (
        <PlanDetailModal
          isSelected
          price={roomTypeDetail.roomDetail.amount}
          planDetail={planDetail}
          closeImageUrl={closeImageUrl}
          restaurantImageUrl={restaurantImageUrl}
          close={closePlanDetail}
          roomTypeDetail={roomTypeDetail}
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

export default SelectedRoomsDisplay;
