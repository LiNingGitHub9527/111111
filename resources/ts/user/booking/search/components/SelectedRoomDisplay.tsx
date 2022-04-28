/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, { FC } from 'react';
import { SelectedRoomData } from '../../datatypes';

type Props = {
  selectedRoomData: SelectedRoomData;
  fetchRoomDetail: () => Promise<void>;
};

const SelectedRoomDisplay: FC<Props> = ({
  selectedRoomData: { roomDetail, roomNum, targetPlan },
  fetchRoomDetail,
}) => {
  return (
    <div>
      <div className="choseRoom_area">
        <div className="choseRoom_wrap">
          <div className="choseRoom_upper">
            <div
              className="choseRoom_img"
              style={{ backgroundImage: `url(${roomDetail.images[0]})` }}
            />
            <p className="choseRoom_title">
              {roomDetail.name + '　'}
              {roomDetail.bed_sum > 0 && roomDetail.bed_sum + 'ベッド'}
              {Boolean(targetPlan.is_meal) &&
                targetPlan.meal_types.length + '食付き'}
            </p>
            <div className="choseRoom_price_block">
              <p className="choseRoom_price_text">{roomNum + 1}部屋目の合計</p>
              <p className="choseRoom_price">
                ¥{roomDetail.amount.toLocaleString()}
              </p>
            </div>
          </div>
          <div className="choseRoom_footer">
            <a
              className="cursor-pointer check_detail_box"
              onClick={fetchRoomDetail}
            >
              <div className="check_detail_btn">詳細確認</div>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SelectedRoomDisplay;
