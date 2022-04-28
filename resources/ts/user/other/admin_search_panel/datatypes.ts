import { ReservationBlock } from '../datatypes';

export type PeopleNums = {
  [reservationBlockToken: string]: number;
};

export type Reservation = {
  block: ReservationBlock;
  peopleNum: number;
};

export type Reservations = {
  [businessDateStr: string]: Reservation[];
};
