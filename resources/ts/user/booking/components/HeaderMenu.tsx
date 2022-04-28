import React, { FC } from 'react';
import ReactDOM from 'react-dom';
import { BusinessType, BUSINESS_TYPE } from '../../../common/datatypes';

type Props = {
  currentPage: number | null;
  businessType: BusinessType;
};

const HeaderMenu: FC<Props> = ({ currentPage, businessType }) => {
  const isHotel = businessType === BUSINESS_TYPE.HOTEL;

  return (
    <div className="common_header_wrap">
      <div className="common_header_block" style={{ height: '50px' }}>
        <div className="header_menu">
          <ul className="header_menu_wrap">
            <li
              className={[
                'reservation_completed_header_list',
                currentPage === 1 && 'active',
              ]
                .filter(Boolean)
                .join(' ')}
              id="header-search"
            >
              お部屋選択
            </li>
            <li
              className={[
                'reservation_completed_header_list',
                currentPage === 3 && 'active',
              ]
                .filter(Boolean)
                .join(' ')}
              id="header-input"
            >
              {isHotel ? '宿泊者' : '予約情報を'}入力
            </li>
            <li
              className="reservation_completed_header_list"
              id="header-complete"
            >
              予約完了
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default HeaderMenu;

const element = document.getElementById('user__booking__headerMenu');
if (element) {
  const { current_page, business_type } = element.dataset;
  const currentPage = current_page ? parseInt(current_page, 10) : null;
  const businessType = business_type
    ? (parseInt(business_type, 10) as BusinessType)
    : BUSINESS_TYPE.HOTEL;
  ReactDOM.render(
    <HeaderMenu currentPage={currentPage} businessType={businessType} />,
    element
  );
}
