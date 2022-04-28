/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC, useCallback, useState } from 'react';
import {
  RoomType,
  RoomTypeDetail,
  RoomTypeDetailResponseData,
} from '../../datatypes';
import api from '../../../../common/utils/api';
import { DEFAULT_ERROR_MESSAGE } from '../../../booking/search_panel/constants';
import { CheckIcon } from '@heroicons/react/outline';
import RoomTypeDetailModal from './RoomTypeDetailModal';
import Spinner from '../../../booking/components/Spinner';

type Props = {
  closeImageUrl: string;
  roomTypeDetailUrl: string;
  roomTypes: RoomType[];
  value: string;
  onChange: (value: string) => void;
};

const RoomTypeSelect: FC<Props> = ({
  closeImageUrl,
  roomTypeDetailUrl,
  roomTypes,
  value,
  onChange,
}) => {
  const [isRoomTypeDetailFetching, setIsRoomTypeDetailFetching] =
    useState<boolean>(false);
  const [roomTypeDetail, setRoomTypeDetail] = useState<RoomTypeDetail | null>(
    null
  );

  const createRoomTypeSelectButtonClickHandler = useCallback(
    (roomType: RoomType) =>
      (_event: React.MouseEvent<HTMLButtonElement>): void => {
        onChange(roomType.room_type_token);
      },
    [onChange]
  );

  const createRoomTypeDetailOpenHandler = useCallback(
    (roomType: RoomType) =>
      async (_event: React.MouseEvent<HTMLElement>): Promise<void> => {
        if (!roomTypeDetailUrl) {
          return;
        }

        setIsRoomTypeDetailFetching(true);
        try {
          const data = (await api.get(roomTypeDetailUrl, {
            room_type_token: roomType.room_type_token,
          })) as RoomTypeDetailResponseData;
          if (data.status === 'OK') {
            setRoomTypeDetail(data.data.room_type_detail);
          } else {
            alert(data.message);
          }
        } catch (_error) {
          alert(DEFAULT_ERROR_MESSAGE);
        } finally {
          setIsRoomTypeDetailFetching(false);
        }
      },
    [roomTypeDetailUrl]
  );

  const closeRoomTypeDetail = useCallback((): void => {
    setRoomTypeDetail(null);
  }, []);

  return (
    <>
      <div>
        {roomTypes.map((roomType: RoomType, roomTypeIndex: number) => (
          <div
            key={`room-type-option-${roomTypeIndex}`}
            className="p-0 bg-white room_menu_block"
          >
            <div>
              <div className="relative w-32 h-0 pb-7/10">
                <img
                  className="absolute top-0 left-0 object-cover w-full h-full"
                  src={roomType.room_type_images[0]}
                  alt=""
                />
              </div>
            </div>
            <div className="flex flex-col items-start justify-start w-full px-2 py-1 sm:px-3 sm:py-3 room_menu_contents">
              <a
                className="room_menu_main"
                onClick={createRoomTypeDetailOpenHandler(roomType)}
              >
                {roomType.room_type_name}
              </a>
              <div className="w-full">
                <button
                  disabled={roomType.room_type_token === value}
                  className={[
                    'w-full room_menu_btn_choose plan_choose_btn',
                    roomType.room_type_token === value &&
                      'bg-gray-600 cursor-default',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  style={{ padding: '1px 6px' }}
                  onClick={createRoomTypeSelectButtonClickHandler(roomType)}
                >
                  {roomType.room_type_token === value ? (
                    <div className="flex items-center justify-center text-white">
                      <CheckIcon className="w-5 h-5 mr-2 text-white" />
                      選択中
                    </div>
                  ) : (
                    '選択する'
                  )}
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
      {roomTypeDetail && (
        <RoomTypeDetailModal
          roomTypeDetail={roomTypeDetail}
          closeImageUrl={closeImageUrl}
          close={closeRoomTypeDetail}
        />
      )}
      {isRoomTypeDetailFetching && (
        <div className="fixed top-0 bottom-0 left-0 right-0 flex flex-col items-center justify-center bg-black bg-opacity-50 z-100">
          <Spinner />
        </div>
      )}
    </>
  );
};

export default RoomTypeSelect;
