/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC, useCallback, useState } from 'react';
import Slider, { LazyLoadTypes } from 'react-slick';
import { useVh } from '../../../../common/hooks/use-vh';
import { RoomTypeDetail } from '../../datatypes';

type Props = {
  roomTypeDetail: RoomTypeDetail;
  closeImageUrl?: string;
  close: VoidFunction;
};

const RoomTypeDetailModal: FC<Props> = ({
  roomTypeDetail,
  closeImageUrl,
  close,
}) => {
  const [currentSlide, setCurrentSlide] = useState<number>(0);

  const slickSettings = {
    autoplay: true,
    autplaySpeed: 100,
    dots: false,
    slideToShow: 1,
    arrows: true,
    infinite: true,
    accessibility: true,
    swipe: true,
    lazyLoad: 'progressive' as LazyLoadTypes,
    beforeChange: (_current: number, next: number): void =>
      setCurrentSlide(next),
    prevArrow: (
      <img
        src="/static/common/images/right-arrow 1.png"
        alt="prev"
        className="slide-arrow prev-arrow"
      />
    ),
    nextArrow: (
      <img
        src="/static/common/images/right-arrow 1.png"
        alt="next"
        className="slide-arrow next-arrow"
      />
    ),
  };

  const handleCloseButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      close();
    },
    [close]
  );

  const vh = useVh();

  return (
    <>
      <div
        className="static-search_panel__wrapper room_type_wrap"
        style={{ top: `calc(10px + (${100 * vh}px - 10px) / 2)` }}
      >
        <div
          className="flex items-center justify-start rounded-t-lg room_type_title"
          style={{ padding: '0 8px 0 16px' }}
        >
          <div className="mr-4">
            <button onClick={handleCloseButtonClick}>
              {closeImageUrl ? (
                <img src={closeImageUrl} alt="" className="detail_cancel_btn" />
              ) : (
                <span className="font-bold">×</span>
              )}
            </button>
          </div>
        </div>
        <div
          className="overflow-scroll"
          style={{ maxHeight: `calc(${100 * vh}px - 128px)` }}
        >
          <div className="detail_wrap">
            <ul className="relative detail_img_box">
              <Slider {...slickSettings}>
                {roomTypeDetail.images.map((image) => (
                  <li key={image} className="slide_img">
                    <img src={image} alt="" />
                  </li>
                ))}
              </Slider>
              {roomTypeDetail.images.length > 1 && (
                <div className="slick-counter">
                  <span className="current">{currentSlide + 1}</span>
                  {' / '}
                  <span className="total">{roomTypeDetail.images.length}</span>
                </div>
              )}
            </ul>
            <div className="detail_main">
              <div className="facility_box">
                <p style={{ width: '100%' }}>{roomTypeDetail.name}</p>
              </div>
              <div className="facility_box">
                <p>最大人数</p>
                <p>{roomTypeDetail.adult_num}人</p>
              </div>
              {(roomTypeDetail.hard_items ?? []).length > 0 && (
                <>
                  <div className="facility_title box-content">
                    <p className="facility_text">部屋の設備</p>
                  </div>
                  <div className="facility_detail">
                    <p>
                      {roomTypeDetail.hard_items.map((item) => item + '　')}
                    </p>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      </div>
      <div className="static-search_panel__filter common_popup_filter" />
    </>
  );
};

export default RoomTypeDetailModal;
