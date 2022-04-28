/* eslint-disable jsx-a11y/anchor-is-valid */
/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useState } from 'react';
import { useVh } from '../../../common/hooks/use-vh';
import { BusinessType, BUSINESS_TYPE } from '../../../common/datatypes';
import { Hotel, HotelNote } from '../datatypes';

type Props = {
  closeImageUrl?: string;
  hotel: Hotel;
  hotelNotes: HotelNote[];
  businessType?: BusinessType;
  cancelDescMessage?: string;
  noShowDescMessage?: string;
};

const HotelPolicyModalLauncher: FC<Props> = ({
  closeImageUrl,
  hotel,
  hotelNotes,
  businessType = BUSINESS_TYPE.HOTEL,
  cancelDescMessage,
  noShowDescMessage,
}) => {
  const isHotel = businessType === BUSINESS_TYPE.HOTEL;
  const facilityDisplay = isHotel ? 'ホテル' : '施設';

  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);

  const handleClick = useCallback(
    (_event: React.MouseEvent<HTMLDivElement>): void => {
      setIsModalOpen(true);
    },
    []
  );

  const handleCloseButtonClick = useCallback(
    (_event: React.MouseEvent<HTMLButtonElement>): void => {
      setIsModalOpen(false);
    },
    []
  );

  const vh = useVh();

  return (
    <>
      <div
        className="flex items-center justify-center h-12 bg-gray-100 cursor-pointer"
        onClick={handleClick}
      >
        <div>
          <a
            className="text-sm font-medium underline"
            style={{ color: '#2185D0' }}
          >
            {facilityDisplay}ポリシーの確認
          </a>
        </div>
      </div>
      {isModalOpen && (
        <>
          <div
            className="z-70 static-search_panel__wrapper room_type_wrap"
            style={{ top: `calc(10px + (${100 * vh}px - 10px) / 2)` }}
          >
            <div
              className="flex items-center justify-start rounded-t-lg room_type_title"
              style={{ padding: '0 8px 0 16px' }}
            >
              <div className="mr-4">
                <button onClick={handleCloseButtonClick}>
                  {closeImageUrl ? (
                    <img
                      src={closeImageUrl}
                      alt=""
                      className="plan_detail_cancel_btn"
                    />
                  ) : (
                    <span className="font-bold">×</span>
                  )}
                </button>
              </div>
              <div className="text-sm room_type_text">
                {facilityDisplay}ポリシー
              </div>
            </div>
            <div
              className="overflow-scroll"
              style={{ maxHeight: `calc(${100 * vh}px - 128px)` }}
            >
              <div>
                {hotel.checkin_start && hotel.checkin_end && hotel.checkout_end && (
                    <div className="checkin_menu_container">
                      <div className="checkin_menu_block">
                        <p>チェックイン</p>
                        <p>{`${hotel.checkin_start
                          .split(':')
                          .slice(0, 2)
                          .join(':')} 〜 ${hotel.checkin_end
                          .split(':')
                          .slice(0, 2)
                          .join(':')}`}</p>
                      </div>
                      <div className="checkin_menu_block">
                        <p>チェックアウト</p>
                        <p>
                          {hotel.checkout_end.split(':').slice(0, 2).join(':')} まで
                        </p>
                      </div>
                    </div>
                  )}
                <div className="introduce_text_block">
                  <div className="introduce_text_section">
                    <p className="introduce_text_title">住所</p>
                    <p className="introduce_text">{hotel.address}</p>
                  </div>
                  {hotelNotes.map((note: HotelNote) => (
                    <div key={note.title} className="introduce_text_section">
                      <p className="introduce_text_title">{note.title}</p>
                      <p className="introduce_text">{note.content}</p>
                    </div>
                  ))}
                  {(cancelDescMessage || noShowDescMessage) && (
                    <div key="cancel_policy" className="introduce_text_section">
                      <p className="introduce_text_title">キャンセルポリシー</p>
                      {cancelDescMessage && (
                        <p className="introduce_text">{cancelDescMessage}</p>
                      )}
                      {noShowDescMessage && (
                        <p className="introduce_text">{noShowDescMessage}</p>
                      )}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
          <div className="z-60 static-search_panel__filter common_popup_filter" />
        </>
      )}
    </>
  );
};

export default HotelPolicyModalLauncher;
