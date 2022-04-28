const range = (num: number): number[] => [...Array(num).keys()];

const formatDate = (date?: Date | null): string =>
  date
    ? [
        date.getFullYear(),
        `0${date.getMonth() + 1}`.slice(-2),
        `0${date.getDate()}`.slice(-2),
      ].join('/')
    : '';

export { range, formatDate };
