import { ReservationBlock } from '../datatypes';

export type RoomNums = {
  [reservationBlockToken: string]: number;
};

export type PeopleNumLists = {
  [reservationBlockToken: string]: number[];
};

export type Reservation = {
  block: ReservationBlock;
  roomNum: number;
  peopleNumList: number[];
};

export type Reservations = {
  [businessDateStr: string]: Reservation[];
};
