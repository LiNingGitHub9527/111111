/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC, useState } from 'react';
import Slider, { LazyLoadTypes } from 'react-slick';
import { RoomTypeDetail } from '../../datatypes';

type Props = {
  roomTypeDetail: RoomTypeDetail;
  restaurantImageUrl?: string;
  fetchSelectedRoomData: () => Promise<void>;
};

const RoomDetailDisplay: FC<Props> = ({
  roomTypeDetail: { roomDetail, targetPlan },
  restaurantImageUrl,
  fetchSelectedRoomData,
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

  return (
    <div>
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
            {roomDetail.bed_sum > 0 && roomDetail.bed_sum + 'ベッド / '}
            {Boolean(targetPlan.is_meal) &&
              targetPlan.meal_types.length + '食付き'}
          </p>
          <div className="tag_box">
            {Boolean(targetPlan.is_meal) && (
              <p>
                <img src={restaurantImageUrl} alt="" />
                {targetPlan.meal_type_kana}
              </p>
            )}
          </div>
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
        <a
          className="cursor-pointer common_footer_btn"
          onClick={fetchSelectedRoomData}
        >
          <div className="reservation_complete">この部屋を選択</div>
        </a>
      </div>
    </div>
  );
};

export default RoomDetailDisplay;
