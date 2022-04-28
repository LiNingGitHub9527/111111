import dayjs, { Dayjs } from 'dayjs';
import 'dayjs/locale/ja';
import customParseFormat from 'dayjs/plugin/customParseFormat';

dayjs.locale('ja');
dayjs.extend(customParseFormat);

export const buildDayjs = (
  date?: string | number | Dayjs | Date | null,
  format?: string
): Dayjs => dayjs(date, format);
