export type ResponseStatus = 'OK' | 'FAIL';

export type RoomType = {
  room_type_name: string;
  room_type_token: string;
  room_type_images: string[];
};

export type ReservationBlock = {
  reservation_block_token: string;
  is_available: number; // 0: unavailable, 1: available
  person_capacity: number;
  price: number;
  start_time: string; // HH:mm(ex. 08:00)
  end_time: string; // HH:mm(ex. 25:30)
  room_num: number;
};

export type ReservationBlocks = {
  // businessDateStr: 営業開始時間が含まれる日(yyyy-MM-dd)
  [businessDateStr: string]: ReservationBlock[];
};

export type ReservationBlocksQueryParams = {
  hotel_id: number;
  room_type_token: string;
  start_date: string; // yyyy-MM-dd
  end_date: string; // yyyy-MM-dd
  is_available: boolean; // if true, response contains only data with "is_available: 1".
  url_param: string;
};

export type ReservationBlocksResponseData = {
  code: number;
  status: ResponseStatus;
  data: {
    reservation_blocks: ReservationBlocks;
  };
  message: string;
};

export type ReservationsPostResponseData = {
  code: number;
  status: ResponseStatus;
  data?: unknown;
  message: string;
};

export type RoomTypeDetail = {
  name: string;
  room_num: number;
  adult_num: number;
  child_num: number;
  room_size: number;
  images: string[];
  hard_items: string[];
};

export type RoomTypeDetailResponseData = {
  code: number;
  status: ResponseStatus;
  data: {
    room_type_detail: RoomTypeDetail;
  };
  message: string;
};
