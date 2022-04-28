/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC, useCallback, useState } from 'react';
import Slider, { LazyLoadTypes } from 'react-slick';
import { useVh } from '../../../../common/hooks/use-vh';
import { RoomTypeDetail } from '../../datatypes';
import CancelButton from './CancelButton';

type Props = {
  isSelected?: boolean;
  roomSelectIndex: number;
  ageNumsKana: string[] | null;
  roomTypeDetail: RoomTypeDetail;
  closeImageUrl?: string;
  close: VoidFunction;
  cancel?: VoidFunction;
};

const RoomDetailModal: FC<Props> = ({
  isSelected = false,
  roomSelectIndex,
  ageNumsKana,
  roomTypeDetail: { roomDetail },
  closeImageUrl,
  close,
  cancel,
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

  const handleCancelButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      cancel && cancel();
      close();
    },
    [cancel, close]
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
          <div
            className="text-sm room_type_text"
            style={{ maxWidth: `calc(100% - ${40 + (isSelected ? 64 : 0)}px)` }}
          >
            {roomSelectIndex + 1}部屋目
            {ageNumsKana?.[roomSelectIndex]
              ? `\n（${ageNumsKana[roomSelectIndex]}）`
              : ''}
          </div>
          {isSelected && (
            <div className="absolute right-2" style={{ top: 11 }}>
              <CancelButton onClick={handleCancelButtonClick}>
                選び直す
              </CancelButton>
            </div>
          )}
        </div>
        <div
          className="overflow-scroll"
          style={{ maxHeight: `calc(${100 * vh}px - 128px)` }}
        >
          <div className="detail_wrap">
            <ul className="relative detail_img_box">
              <Slider {...slickSettings}>
                {roomDetail.images.map((image) => (
                  <li key={image} className="slide_img">
                    <img src={image} alt="" />
                  </li>
                ))}
              </Slider>
              {roomDetail.images.length > 1 && (
                <div className="slick-counter">
                  <span className="current">{currentSlide + 1}</span>
                  {' / '}
                  <span className="total">{roomDetail.images.length}</span>
                </div>
              )}
            </ul>
            <div className="detail_main">
              <p className="detail_title">
                {roomDetail.name + '　'}
                {roomDetail.bed_sum > 0 && roomDetail.bed_sum + 'ベッド'}
              </p>
              <div className="facility_box">
                <p>最大人数</p>
                <p>大人{roomDetail.adult_num}人</p>
              </div>
              <div className="facility_box">
                <p>ベット</p>
                <p>{roomDetail.beds_kana}</p>
              </div>
              <div className="facility_box">
                <p>部屋</p>
                <p>{roomDetail.room_size}m&sup2;</p>
              </div>
              {roomDetail.hard_items && roomDetail.hard_items.length > 0 && (
                <>
                  <div className="facility_title box-content">
                    <p className="facility_text">部屋の設備</p>
                  </div>
                  <div className="facility_detail">
                    <p>{roomDetail.hard_items.map((item) => item + '　')}</p>
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

export default RoomDetailModal;
