import { BusinessType } from '../../common/datatypes';

export type Plan = {
  id: number;
  cover_image: string;
  plan_name: string;
  plan_token: string;
};

export type ResponseStatus = 'ok' | 'error';

export type CancelPolicy = {
  cancel_charge_rate: number;
  created_at: string;
  deleted_at: string | null;
  free_day: number;
  free_time: number;
  hotel_id: number;
  id: number;
  is_free_cancel: number;
  name: string;
  no_show_charge_rate: number;
  updated_at: string;
};

export type PlanDetail = {
  calculate_method: number;
  calculate_num: number;
  cancel_desc?: string;
  cancel_policy?: CancelPolicy;
  cancel_policy_id: number;
  checkin_start_time: string | null;
  cover_image: string;
  created_at: string;
  day_ago: number;
  deleted_at: string | null;
  description: string;
  existing_plan_id: number;
  fee_class_type: number;
  hotel_id: number;
  id: number;
  is_day_ago: number;
  is_max_stay_days: number;
  is_meal: number;
  is_min_stay_days: number;
  is_new_plan: number;
  last_checkin_time: string | null;
  last_checkout_time: string | null;
  max_stay_days: number;
  meal_type_kana?: string;
  meal_types: number[];
  min_stay_days: number;
  min_stay_time: string | null;
  name: string;
  no_show_desc?: string;
  plan_token?: string;
  prepay: number;
  public_status: number;
  room_type_ids: number[];
  sort_num: number;
  stay_type: number;
  up_or_down: number;
  updated_at: string;
};

export type PlanDetailResponseData = {
  message?: string;
  plan_detail: PlanDetail;
  res: ResponseStatus;
};

export type Bed = {
  bed_num: number;
  bed_size: number;
  bed_type: string;
  room_type_id: string;
};

export type Room = {
  adult_num: number;
  amount: number;
  amount_breakdown: {
    [datetime: string]: {
      all_amount: number;
      class_amount: number;
      class_person_num: number;
      class_type: number;
      date: string;
      kids_amount: number;
    };
    // amount: number;
  };
  bed_sum: number;
  beds: Bed[];
  beds_kana?: string;
  child_num: number;
  date_stock_nums: {
    [date: string]: number;
  };
  hard_items?: string[];
  images: string[];
  name: string;
  room_size: number;
  room_token?: string;
  room_type_id: number;
  sort_num: number;
};

export type ChildNum = {
  age_end: number;
  age_start: number;
  num: string;
};

export type RoomTypes = {
  ageNums: {
    adult_num: string;
    child_num?: ChildNum[];
  };
  ageNumsKana: string[];
  planToken: string;
  stayAbleRooms: Room[][];
  targetPlan: PlanDetail;
};

export type PlanRoomTypesResponseData = {
  message?: string;
  res: ResponseStatus;
  roomTypes: RoomTypes;
};

export type Hotel = {
  address: string;
  agreement_date: string;
  business_type: BusinessType;
  checkin_end: string;
  checkin_start: string;
  checkout_end: string;
  client_id: number;
  created_at: string;
  crm_base_id: number | null;
  deleted_at: string | null;
  email: string;
  id: number;
  last_sync_time: string | null;
  logo_img: string | null;
  name: string;
  person_in_charge: string;
  rate_plan_id: number;
  sync_status: number;
  tel: string;
  tema_login_id: string;
  tema_login_password: string;
  updated_at: string;
};

export type HotelNote = {
  content: string;
  title: string;
};

export type SelectedRoomData = {
  hotel: Hotel;
  hotelNotes: HotelNote[];
  roomDetail: Room;
  roomNum: number;
  roomToken: string;
  targetPlan: PlanDetail;
};

export type SelectedRoomDataResponseData = {
  hideRoomTokens: string[];
  isInStock: boolean;
  is_all_selected: boolean;
  message?: string;
  res: ResponseStatus;
  selectedRoomData: SelectedRoomData;
};

export type RoomTypeDetail = {
  roomDetail: Room;
  roomNum: number;
  roomToken: string;
  targetPlan: PlanDetail;
};

export type RoomDetailResponseData = {
  message?: string;
  res: ResponseStatus;
  roomTypeDetail: RoomTypeDetail;
};

export type SelectedRoomCancelResponseData = {
  message?: string;
  res: ResponseStatus;
  room_num: number;
  selectedNums: string[];
  showRoomTokens: string[];
};

export type KidsPolicy = {
  age_end: number;
  age_start: number;
  is_forbidden: number;
};

export type PlanSearchError = {
  'adult_num.0'?: string[];
  checkin_date?: string[];
  checkout_date?: string[];
  [key: string]: string[] | undefined;
};

export interface SearchDataRequestParams {
  adult_num: string[];
  checkin_date: string;
  checkout_date: string;
  child_num: string;
  room_num: string;
  url_param: string;
  _token: string;
  // [kidstart_x: string]: string[];
}

export interface DayuseSearchDataRequestParams {
  adult_num: string[];
  checkin_date: string;
  checkin_date_time: string;
  child_num: string;
  room_num: string;
  stay_time: string;
  url_param: string;
  _token: string;
  // [kidstart_x: string]: string[];
}

export type SearchData = {
  ageNums: {
    adult_num: string;
    child_num?: ChildNum[];
  };
  hotel: Hotel;
  hotelNotes: HotelNote[];
  inOutDate: string[];
  plans: Plan[];
  title: string;
  urlParam: string;
};

export type SearchDataResponseData = {
  error?: PlanSearchError | string;
  message?: string;
  res: ResponseStatus;
  searchData: SearchData;
};

export type RoomInfo = {
  amounts: {
    [planToken: string]: number;
  };
  currentSelectIndex: number;
  room: Room;
  planDetails: PlanDetail[];
};

export type CheckinTimeListResponseData = {
  message?: string;
  min_max: string[];
  res: ResponseStatus;
};

export type StayTimeListResponseData = {
  message?: string;
  res: ResponseStatus;
  stay_time: number[];
};
