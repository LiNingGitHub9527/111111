import { useCallback, useEffect, useState } from 'react';

type WindowSize = {
  width: number;
  height: number;
};

export const useWindowSize = (): WindowSize => {
  const [windowSize, setWindowSize] = useState<WindowSize>({
    width: 0,
    height: 0,
  });

  const resize = useCallback((): void => {
    setWindowSize({
      width: window.innerWidth,
      height: window.innerHeight,
    });
  }, []);

  useEffect(() => {
    resize();

    window.addEventListener('resize', resize);
    const cleanup = (): void => {
      window.removeEventListener('resize', resize);
    };

    return cleanup;
  }, [resize]);

  return windowSize;
};
