/* eslint-disable jsx-a11y/no-onchange */
import React, { FC, useCallback, useMemo, useRef, useState } from 'react';
import ReactDOM from 'react-dom';
import axios, { AxiosResponse } from 'axios';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import $ from 'jquery';
import 'gasparesganga-jquery-loading-overlay';
import { useBrowserBackWithNoCache } from '../../../../common/hooks/use-browser-back-with-no-cache';
import { useCsrfToken } from '../../../../common/hooks/use-csrf-token';
import { Hotel, HotelNote } from '../../../booking/datatypes';
import { ReservationsPostResponseData, RoomType } from '../../datatypes';
import { Reservation, Reservations } from '../datatypes';
import { DEFAULT_ERROR_MESSAGE } from '../../../booking/search_panel/constants';
import { CalendarIcon, OfficeBuildingIcon } from '@heroicons/react/solid';
import RoomTypeSelect from './RoomTypeSelect';
import ReservationSelect from './ReservationSelect';
import HotelPolicyModalLauncher from '../../../booking/components/HotelPolicyModalLauncher';
import Spinner from '../../../booking/components/Spinner';

type Props = {
  lpParam?: string;
  roomTypes: RoomType[];
  hotel?: Hotel;
  hotelNotes?: HotelNote[];
  cancelDescMessage: string;
  noShowDescMessage: string;
  roomTypeDetailUrl: string;
  reservationBlockListUrl: string;
  infoInputUrl: string;
  infoInputRenderUrl: string;
  closeButtonImageUrl: string;
  closeImageUrl: string;
  // errors?: unknown;
};

const ReserveConditionForm: FC<Props> = ({
  lpParam,
  roomTypes,
  hotel,
  hotelNotes,
  cancelDescMessage,
  noShowDescMessage,
  roomTypeDetailUrl,
  reservationBlockListUrl,
  infoInputUrl,
  infoInputRenderUrl,
  closeButtonImageUrl,
  closeImageUrl,
  // errors,
}) => {
  // console.log('hotel:');
  // console.log(hotel);

  // console.log('room types');
  // console.log(roomTypes);

  useBrowserBackWithNoCache();

  const csrfToken = useCsrfToken();
  // console.log('csrf token:');
  // console.log(csrfToken);

  const [selectedRoomTypeToken, setSelectedRoomTypeToken] =
    useState<string>('');

  const [selectedReservations, setSelectedReservations] =
    useState<Reservations>({});

  const reservationSelectRef = useRef<HTMLDivElement | null>(null);

  const scrollToReservationSelect = useCallback((): void => {
    const top = reservationSelectRef.current?.offsetTop;
    if (top) {
      window.scrollTo({
        top,
        left: 0,
        behavior: 'smooth',
      });
    }
  }, []);

  const handleRoomTypeChange = useCallback(
    (value: string): void => {
      if (value !== selectedRoomTypeToken) {
        setSelectedRoomTypeToken(value);
        setSelectedReservations({});
        scrollToReservationSelect();
      }
    },
    [scrollToReservationSelect, selectedRoomTypeToken]
  );

  const handleChangeReservations = useCallback(
    (reservations: Reservations): void => {
      // console.log('change reservations:');
      // console.log(reservations);
      setSelectedReservations(reservations);
    },
    []
  );

  const selectedReservationsCount = useMemo((): number => {
    return Object.values(selectedReservations)
      .map((v: Reservation[]) => v.length)
      .reduce((acc, cur) => acc + cur, 0);
  }, [selectedReservations]);

  const allSelected = selectedReservationsCount > 0;

  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const submitButtonRef = useRef<HTMLButtonElement | null>(null);

  const postReservations = useCallback(async (): Promise<void> => {
    // console.log('selected reservations:');
    // console.log(selectedReservations);
    const selectedBlocks = Object.values(selectedReservations)[0].map(
      (reservation: Reservation) => ({
        reservation_block_token: reservation.block.reservation_block_token,
        person_num: reservation.peopleNumList,
      })
    );
    const params = {
      selected_blocks: selectedBlocks,
    };
    // console.log('post selected blocks:');
    // console.log(params);
    const client = axios.create({
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    const res = (await client.post(
      infoInputUrl,
      params
    )) as AxiosResponse<ReservationsPostResponseData>;
    // console.log(res);
    if (res.data.status === 'FAIL') {
      alert(res.data.message ?? DEFAULT_ERROR_MESSAGE);
    }
  }, [csrfToken, infoInputUrl, selectedReservations]);

  const handleFormSubmit = useCallback(
    async (event?: React.FormEvent<HTMLFormElement>): Promise<void> => {
      event?.preventDefault();
      if (!infoInputUrl) {
        return;
      }

      setIsSubmitting(true);
      if (event && submitButtonRef.current) {
        $(submitButtonRef.current).LoadingOverlay('show');
      }

      try {
        await postReservations();
        window.location.href = infoInputRenderUrl;
      } catch (_error) {
        alert(DEFAULT_ERROR_MESSAGE);
        $(submitButtonRef.current).LoadingOverlay('hide');
        setIsSubmitting(false);
      }
    },
    [infoInputRenderUrl, infoInputUrl, postReservations]
  );

  return (
    <>
      <div>
        <div className="static-search_panel__inner">
          <div className="mb-2.5 static-search_panel__item_wrap">
            <div className="mb-1.5 static-search_panel__item_text static-search_panel__item_title">
              <div className="flex items-center justify-start">
                <div className="mr-2.5">
                  <OfficeBuildingIcon className="w-6 h-6 opacity-30" />
                </div>
                <div>部屋タイプ</div>
              </div>
            </div>
            <div>
              <RoomTypeSelect
                roomTypeDetailUrl={roomTypeDetailUrl}
                closeImageUrl={closeImageUrl}
                roomTypes={roomTypes}
                value={selectedRoomTypeToken}
                onChange={handleRoomTypeChange}
              />
            </div>
          </div>
          {selectedRoomTypeToken && (
            <div className="static-search_panel__item_wrap">
              <div className="mb-1.5 static-search_panel__item_text static-search_panel__item_title">
                <div className="flex items-center justify-start">
                  <div className="mr-2.5">
                    <CalendarIcon className="w-6 h-6 opacity-30" />
                  </div>
                  <div>ご利用日時 / ご利用人数</div>
                </div>
              </div>
              <div className="static-search_panel__item_input_area">
                <div>
                  <ReservationSelect
                    value={selectedReservations}
                    hotel={hotel}
                    lpParam={lpParam}
                    roomTypeToken={selectedRoomTypeToken}
                    reservationBlockListUrl={reservationBlockListUrl}
                    closeButtonImageUrl={closeButtonImageUrl}
                    onChange={handleChangeReservations}
                  />
                </div>
              </div>
            </div>
          )}
          <div style={{ height: selectedRoomTypeToken ? 64 : 128 }} />
          <div ref={reservationSelectRef} />
        </div>
      </div>
      {hotel && hotelNotes && (
        <div className="fixed left-0 right-0 z-50" style={{ bottom: 45 }}>
          <HotelPolicyModalLauncher
            closeImageUrl={closeImageUrl}
            hotel={hotel}
            hotelNotes={hotelNotes}
            businessType={hotel.business_type}
            cancelDescMessage={cancelDescMessage}
            noShowDescMessage={noShowDescMessage}
          />
        </div>
      )}
      <form action={infoInputUrl} method="post" onSubmit={handleFormSubmit}>
        <div className="z-40 footer_fix_btn">
          <button
            disabled={!allSelected || isSubmitting}
            ref={submitButtonRef}
            type="submit"
            className="rounded-none common_footer_btn"
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
      {isSubmitting && (
        <div className="fixed top-0 bottom-0 left-0 right-0 flex flex-col items-center justify-center bg-black bg-opacity-50 z-100">
          <Spinner />
        </div>
      )}
    </>
  );
};

export default ReserveConditionForm;

const element = document.getElementById(
  'user__other__search_panel__searchPanelForm'
);
if (element) {
  const {
    lp_param,
    room_types,
    hotel,
    hotel_notes,
    cancel_desc_message,
    no_show_desc_message,
    room_type_detail_url,
    reservation_block_list_url,
    info_input_url,
    info_input_render_url,
    close_button_image_url,
    close_image_url,
    // errors,
  } = element.dataset;
  // console.log('dataset:');
  // console.log(element.dataset);
  ReactDOM.render(
    <ReserveConditionForm
      lpParam={lp_param}
      roomTypes={room_types ? JSON.parse(room_types) : []}
      hotel={hotel ? JSON.parse(hotel) : undefined}
      hotelNotes={hotel_notes ? JSON.parse(hotel_notes) : undefined}
      cancelDescMessage={cancel_desc_message ?? ''}
      noShowDescMessage={no_show_desc_message ?? ''}
      roomTypeDetailUrl={room_type_detail_url ?? ''}
      reservationBlockListUrl={reservation_block_list_url ?? ''}
      infoInputUrl={info_input_url ?? ''}
      infoInputRenderUrl={info_input_render_url ?? ''}
      closeButtonImageUrl={close_button_image_url ?? ''}
      closeImageUrl={close_image_url ?? ''}
      // errors={errors ? JSON.parse(errors) : undefined}
    />,
    element
  );
}
