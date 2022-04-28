module.exports = {
  extends: ['stylelint-config-recommended', 'stylelint-config-rational-order'],
  rules: {
    indentation: 2,
    'at-rule-no-unknown': [
      true,
      {
        ignoreAtRules: ['extend', 'extends', 'tailwind', 'layer'],
      },
    ],
    'unit-allowed-list': [
      'px',
      '%',
      'em',
      'rem',
      'vw',
      'vh',
      'vmin',
      'vmax',
      's',
      'deg',
      'fr',
    ],
  },
};
