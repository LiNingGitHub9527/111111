/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/anchor-is-valid */
import React, {
  FC,
  memo,
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';
import ReactDOM from 'react-dom';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import $ from 'jquery';
import 'gasparesganga-jquery-loading-overlay';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import { useVh } from '../../../../common/hooks/use-vh';
import api from '../../../../common/utils/api';
import {
  Plan,
  PlanDetail,
  PlanDetailResponseData,
  PlanRoomTypesResponseData,
  RoomTypes,
} from '../../datatypes';
import CancelButton from './CancelButton';
import PlanDetailDisplay from './PlanDetailDisplay';
import RoomSelect from './RoomSelect';

type PlanNameProps = {
  name: string;
  onClick: (event: React.MouseEvent<HTMLAnchorElement>) => void;
};

const PlanNameUnmemorized: FC<PlanNameProps> = ({ name, onClick }) => {
  return (
    <a className="room_menu_main" onClick={onClick}>
      {name}
    </a>
  );
};
const PlanName = memo(PlanNameUnmemorized);

type Props = {
  plans: Plan[];
  closeImageUrl?: string;
  planDetailUrl?: string;
  restaurantImageUrl?: string;
  planRoomTypeUrl?: string;
  selectedRoomDataUrl?: string;
  roomDetailUrl?: string;
  infoInputUrl?: string;
  selectedRoomCancelUrl?: string;
};

const PlanSelect: FC<Props> = ({
  plans,
  closeImageUrl,
  planDetailUrl,
  restaurantImageUrl,
  planRoomTypeUrl,
  selectedRoomDataUrl,
  roomDetailUrl,
  infoInputUrl,
  selectedRoomCancelUrl,
}) => {
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);
  const [showAll, setShowAll] = useState<boolean>(false);
  const [selectedPlanDetail, setSelectedPlanDetail] =
    useState<PlanDetail | null>(null);
  const [roomTypes, setRoomTypes] = useState<RoomTypes | null>(null);
  const [allSelected, setAllSelected] = useState<boolean>(false);
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

  const submitButtonRef = useRef<HTMLButtonElement | null>(null);

  const csrfToken = useCsrfToken();

  const createPlanSelectButtonClickHandler = useCallback(
    (plan: Plan) =>
      (_event: React.MouseEvent<HTMLButtonElement>): void => {
        setSelectedPlan(plan);
      },
    []
  );

  const handleCancelButtonClick = useCallback(
    (_event: React.MouseEvent<HTMLButtonElement>): void => {
      setAllSelected(false);
      setSelectedPlan(null);
      setRoomTypes(null);
    },
    []
  );

  const handleShowAllClick = useCallback(
    (_event: React.MouseEvent<HTMLDivElement>): void => {
      setShowAll(true);
    },
    []
  );

  const createPlanDetailFetchHandler = useCallback(
    (planToken: string) =>
      async (_event: React.MouseEvent<HTMLAnchorElement>): Promise<void> => {
        if (!planDetailUrl || !csrfToken) {
          return;
        }

        const data = (await api.post(planDetailUrl, {
          plan_token: planToken,
          _token: csrfToken,
        })) as PlanDetailResponseData;
        if (data.res === 'ok') {
          setSelectedPlanDetail(data.plan_detail);
        } else {
          alert(data.message);
        }
      },
    [csrfToken, planDetailUrl]
  );

  const handlePlanDetailCloseButtonClick = useCallback(
    (_event: React.MouseEvent<HTMLButtonElement>): void => {
      setSelectedPlanDetail(null);
    },
    []
  );

  const selectPlanFromDetail = useCallback((): void => {
    if (!selectedPlanDetail) {
      return;
    }

    const plan = plans.find((plan: Plan) => plan.id === selectedPlanDetail.id);
    setSelectedPlan(plan ?? null);
    setSelectedPlanDetail(null);
  }, [plans, selectedPlanDetail]);

  const fetchPlanRoomTypes = useCallback(async (): Promise<void> => {
    if (!planRoomTypeUrl || !csrfToken || !selectedPlan) {
      return;
    }

    const data = (await api.post(planRoomTypeUrl, {
      plan_token: selectedPlan.plan_token,
      _token: csrfToken,
    })) as PlanRoomTypesResponseData;
    if (data.res === 'ok') {
      setRoomTypes(data.roomTypes);
    } else {
      alert(data.message);
    }
  }, [csrfToken, planRoomTypeUrl, selectedPlan]);

  useEffect(() => {
    fetchPlanRoomTypes();
  }, [fetchPlanRoomTypes]);

  const changeAllSelected = useCallback((selected: boolean): void => {
    setAllSelected(selected);
  }, []);

  const handleFormSubmit = useCallback(
    (_event: React.FormEvent<HTMLFormElement>): void => {
      setIsSubmitting(true);
      if (submitButtonRef.current) {
        $(submitButtonRef.current).LoadingOverlay('show');
      }
    },
    []
  );

  const vh = useVh();

  return (
    <>
      <div className="room_type_wrap">
        <div className="room_type_title">
          <p className="room_type_text">宿泊プランをご選択ください</p>
          {selectedPlanDetail && (
            <button onClick={handlePlanDetailCloseButtonClick}>
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
          )}
          {selectedPlan && <CancelButton onClick={handleCancelButtonClick} />}
        </div>
        {!selectedPlanDetail ? (
          <>
            <div
              className="overflow-scroll"
              style={{ maxHeight: showAll ? `${75 * vh}px` : `${70 * vh}px` }}
            >
              {!selectedPlan ? (
                plans.map((plan, index) => (
                  <div
                    key={plan.id}
                    className={[
                      'room_menu_block',
                      !showAll && index >= 10 && 'hidden',
                    ]
                      .filter(Boolean)
                      .join(' ')}
                  >
                    <div className="room_menu_img">
                      <img src={plan.cover_image} alt="" />
                    </div>
                    <div className="room_menu_contents">
                      <PlanName
                        name={plan.plan_name}
                        onClick={createPlanDetailFetchHandler(plan.plan_token)}
                      />
                      <div className="room_menu_btn">
                        <button
                          className="room_menu_btn_choose plan_choose_btn"
                          style={{ padding: '1px 6px' }}
                          onClick={createPlanSelectButtonClickHandler(plan)}
                        >
                          このプランを選択
                        </button>
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="room_menu_block planSelected">
                  <PlanName
                    name={selectedPlan.plan_name}
                    onClick={createPlanDetailFetchHandler(
                      selectedPlan.plan_token
                    )}
                  />
                </div>
              )}
            </div>
            {!showAll && plans.length > 10 ? (
              <div
                className="cursor-pointer room_type_footer"
                onClick={handleShowAllClick}
              >
                <p>残りのプランを見る - {plans.length - 10}件</p>
              </div>
            ) : (
              <div className="room_type_footer">
                <p>全てのプランが表示されています</p>
              </div>
            )}
          </>
        ) : (
          <PlanDetailDisplay
            planDetail={selectedPlanDetail}
            restaurantImageUrl={restaurantImageUrl}
            selectPlan={selectPlanFromDetail}
          />
        )}
      </div>
      {roomTypes && (
        <RoomSelect
          roomTypes={roomTypes}
          closeImageUrl={closeImageUrl}
          selectedRoomDataUrl={selectedRoomDataUrl}
          roomDetailUrl={roomDetailUrl}
          restaurantImageUrl={restaurantImageUrl}
          changeAllSelected={changeAllSelected}
          selectedRoomCancelUrl={selectedRoomCancelUrl}
        />
      )}
      <form action={infoInputUrl} method="get" onSubmit={handleFormSubmit}>
        <div className="z-50 footer_fix_btn">
          <button
            disabled={!allSelected || isSubmitting}
            ref={submitButtonRef}
            type="submit"
            className="common_footer_btn"
            style={
              !allSelected || isSubmitting
                ? { pointerEvents: 'none', background: '#f1f1f1' }
                : { pointerEvents: 'auto' }
            }
          >
            予約を進める {'>>'}
          </button>
        </div>
      </form>
    </>
  );
};

export default PlanSelect;

const element = document.getElementById('user__booking__search__planSelect');
if (element) {
  const {
    plans: plansJson,
    close_image_url,
    plan_detail_url,
    restaurant_image_url,
    plan_room_type_url,
    selected_room_data_url,
    room_detail_url,
    info_input_url,
    selected_room_cancel_url,
  } = element.dataset;
  const plans: Plan[] = plansJson ? JSON.parse(plansJson) : [];
  ReactDOM.render(
    <PlanSelect
      plans={plans}
      closeImageUrl={close_image_url}
      planDetailUrl={plan_detail_url}
      restaurantImageUrl={restaurant_image_url}
      planRoomTypeUrl={plan_room_type_url}
      selectedRoomDataUrl={selected_room_data_url}
      roomDetailUrl={room_detail_url}
      infoInputUrl={info_input_url}
      selectedRoomCancelUrl={selected_room_cancel_url}
    />,
    element
  );
}
