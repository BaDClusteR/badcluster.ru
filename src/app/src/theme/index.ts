import { createTheme } from '@mantine/core';

/**
 * Minimal JS theme — only values that Mantine needs at runtime
 * (primary color key, default radius name). All visual styling
 * lives in CSS via Mantine's CSS variables — see src/styles/global.css.
 */
export const theme = createTheme({
  primaryColor: 'indigo',
  defaultRadius: 'md',
});