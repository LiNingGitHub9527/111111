import React, { FC, useCallback } from 'react';
import { PlanDetail } from '../../datatypes';

type Props = {
  planDetail: PlanDetail;
  restaurantImageUrl?: string;
  selectPlan: VoidFunction;
};

const PlanDetailDisplay: FC<Props> = ({
  planDetail,
  restaurantImageUrl,
  selectPlan,
}) => {
  const handlePlanSelect = useCallback(
    (_event: React.MouseEvent<HTMLButtonElement>): void => {
      selectPlan();
    },
    [selectPlan]
  );

  return (
    <div className="plan_detail_wrap">
      <ul className="detail_img_box">
        <li className="slide_img">
          <img src={planDetail.cover_image} alt="" />
        </li>
      </ul>
      <div className="detail_main">
        <p className="detail_title">{planDetail.name}</p>
        <div className="tag_box">
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
          <p className="facility_text">キャンセルポリシー</p>
        </div>
        <div className="facility_detail">
          <p className="mr-0">{planDetail.cancel_desc}</p>
          <br />
          <p className="mr-0">{planDetail.no_show_desc}</p>
        </div>
      </div>
      <button
        type="button"
        className="common_footer_btn detail_select_btn"
        onClick={handlePlanSelect}
      >
        <div className="reservation_complete">このプランを選択</div>
      </button>
    </div>
  );
};

export default PlanDetailDisplay;
