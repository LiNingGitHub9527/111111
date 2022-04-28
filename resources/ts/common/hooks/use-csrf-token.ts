export const useCsrfToken = (): string => {
  // use global var csrfToken
  // <script>
  //     var csrfToken = '{{ csrf_token() }}';
  // </script>

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  return csrfToken;
};
