import { useWindowSize } from './use-window-size';

export const useVh = (): number => {
  const { height } = useWindowSize();

  return height * 0.01;
};
