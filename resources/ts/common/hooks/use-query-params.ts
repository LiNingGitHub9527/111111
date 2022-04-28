import queryString, { ParsedQuery } from 'query-string';

export const useQueryParams = (): ParsedQuery<string | boolean> => {
  if (!window?.location?.search) {
    return {};
  }

  return queryString.parse(window.location.search.slice(1), {
    parseBooleans: true,
  });
};
