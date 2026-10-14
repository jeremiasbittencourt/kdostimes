export const theme = {
  colors: {
    primary: '#1B7F3A',
    primaryDark: '#0E5523',
    primaryLight: '#3FB15E',
    accent: '#FFC93C',
    bg: '#F5F7F5',
    surface: '#FFFFFF',
    text: '#1A1F1C',
    textMuted: '#5A665E',
    border: '#D9DFD9',
    danger: '#C8321F',
    warning: '#E6A23C',
  },
  spacing: {
    xs: 4,
    sm: 8,
    md: 16,
    lg: 24,
    xl: 32,
  },
  radius: {
    sm: 6,
    md: 12,
    lg: 20,
    pill: 999,
  },
  typography: {
    heading: {fontSize: 22, fontWeight: '700' as const},
    title: {fontSize: 18, fontWeight: '600' as const},
    body: {fontSize: 15, fontWeight: '400' as const},
    caption: {fontSize: 12, fontWeight: '400' as const},
  },
} as const;

export type Theme = typeof theme;
