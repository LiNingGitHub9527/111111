import React, { FC, useCallback, useState } from 'react';
import { useVh } from '../../../../common/hooks/use-vh';
import Slider, { LazyLoadTypes } from 'react-slick';
import { PlanDetail, RoomTypeDetail } from '../../datatypes';

type Props = {
  isSelected?: boolean;
  price: number;
  planDetail: PlanDetail;
  closeImageUrl?: string;
  restaurantImageUrl?: string;
  selectPlan?: VoidFunction;
  close: VoidFunction;
  roomTypeDetail: RoomTypeDetail;
};

const PlanDetailModal: FC<Props> = ({
  isSelected = false,
  price,
  planDetail,
  closeImageUrl,
  restaurantImageUrl,
  selectPlan,
  close,
  roomTypeDetail: { roomDetail },
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

  const handlePlanSelect = useCallback(
    (_event: React.MouseEvent<HTMLButtonElement>): void => {
      selectPlan && selectPlan();
      close();
    },
    [close, selectPlan]
  );

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
            {planDetail.name +
              (planDetail.is_meal
                ? '　' + `${planDetail.meal_types.length}食付き`
                : '')}
          </div>
        </div>
        <div
          className="overflow-scroll"
          style={{ maxHeight: `calc(${100 * vh}px - 128px)` }}
        >
          <div className="plan_detail_wrap">
            <ul className="detail_img_box">
              <li className="slide_img">
                <img src={planDetail.cover_image} alt="" />
              </li>
            </ul>
            <div className="detail_main">
              <div className="text-xl font-semibold">
                ¥{price.toLocaleString()}
              </div>
              <div className="mt-4 tag_box">
                {Boolean(planDetail.is_meal) && (
                  <p>
                    <img src={restaurantImageUrl} alt="" />
                    {planDetail.meal_type_kana}
                  </p>
                )}
              </div>
              <div className="facility_title box-content">
                <p className="facility_text">プラン説明</p>
              </div>
              <div className="facility_detail">
                <p className="mr-0">{planDetail.description}</p>
              </div>
              <div className="facility_title box-content">
                <p className="facility_text">お部屋情報</p>
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
              <div className="facility_title box-content">
                <p className="facility_text">キャンセルポリシー</p>
              </div>
              <div className="facility_detail">
                <p className="mr-0">{planDetail.cancel_desc}</p>
                <br />
                <p className="mr-0">{planDetail.no_show_desc}</p>
              </div>
            </div>
            {!isSelected && (
              <div style={{ padding: '0 17px 8px' }}>
                <button
                  type="button"
                  className="common_footer_btn detail_select_btn"
                  onClick={handlePlanSelect}
                >
                  <div className="reservation_complete">選択する</div>
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="static-search_panel__filter common_popup_filter" />
    </>
  );
};

export default PlanDetailModal;
