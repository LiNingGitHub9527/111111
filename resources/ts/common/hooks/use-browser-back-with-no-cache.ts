import { useEffect } from 'react';

export const useBrowserBackWithNoCache = (): void => {
  // NOTE: for iOS.
  useEffect(() => {
    const reloadIfPersisted = (event: PageTransitionEvent): void => {
      if (event.persisted) {
        window.location.reload();
      }
    };
    window.addEventListener('pageshow', reloadIfPersisted);
    const cleanup = (): void => {
      window.removeEventListener('pageshow', reloadIfPersisted);
    };

    return cleanup;
  }, []);
};
