module.exports = {
  root: true,
  extends: '@react-native',
  parserOptions: {
    project: './tsconfig.json',
  },
  rules: {
    'react-native/no-inline-styles': 'off',
    '@typescript-eslint/consistent-type-imports': 'warn',
  },
};
