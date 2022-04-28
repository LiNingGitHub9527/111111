import { range } from './scripts';

export const DAY_OF_WEEK = {
  SUN: 0,
  MON: 1,
  TUE: 2,
  WED: 3,
  THU: 4,
  FRI: 5,
  SAT: 6,
} as const;

export type DayOfWeek = typeof DAY_OF_WEEK[keyof typeof DAY_OF_WEEK];

export const toDayOfWeek = (num: number): DayOfWeek =>
  (((num % 7) + 7) % 7) as DayOfWeek;

export const remainingNumberUpToMultiplesOf7 = (num: number): number =>
  (7 - (num % 7)) % 7;

export class Calendar {
  constructor(
    private startingDayOfWeek: number = DAY_OF_WEEK.SUN,
    private isWeeksCountFlexible: boolean = false
  ) {}

  monthDates = (year: number, month: number): Date[] => {
    const monthFirstDate = new Date(year, month - 1);
    const monthFirstDayOfWeek = toDayOfWeek(
      monthFirstDate.getDay() - (monthFirstDate.getDate() - 1)
    );

    const lastDateOfMonth = new Date(year, month, 0);
    const datesCountOfMonth = lastDateOfMonth.getDate();

    const lastMonthDateIndices = range(
      toDayOfWeek(monthFirstDayOfWeek - this.startingDayOfWeek)
    )
      .map((i: number) => -i)
      .reverse();

    const nextMonthDatesCount = this.isWeeksCountFlexible
      ? remainingNumberUpToMultiplesOf7(
          lastMonthDateIndices.length + datesCountOfMonth
        )
      : 7 * 6 - (lastMonthDateIndices.length + datesCountOfMonth);
    const nextMonthDateIndices = range(nextMonthDatesCount).map(
      (i: number) => datesCountOfMonth + i + 1
    );

    const dateIndices = [
      ...lastMonthDateIndices,
      ...range(datesCountOfMonth).map((i: number) => i + 1),
      ...nextMonthDateIndices,
    ];

    return dateIndices.map(
      (dateIndex: number) => new Date(year, month - 1, dateIndex)
    );
  };

  monthDatesByWeek = (year: number, month: number): Date[][] => {
    const dates = this.monthDates(year, month);
    const weeksCount = Math.ceil(dates.length / 7);

    return range(weeksCount).map((weekIndex: number): Date[] =>
      dates.slice(7 * weekIndex, 7 * (weekIndex + 1))
    );
  };
}
