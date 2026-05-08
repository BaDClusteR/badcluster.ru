import type { MediaData } from './types';

/**
 * Default breakpoints mapping viewport `maxWidth` → image width to use.
 * Matches the PHP Picture widget defaults (see BC\Widget\Common\Picture).
 * Use -1 as a key for "no upper bound".
 */
export const DEFAULT_BREAKPOINTS: Record<number, number> = {
  500: 500,
  [-1]: 1000,
};

/**
 * Builds a native <picture> element (or <video> for videos) matching the
 * HTML the backend Picture widget would produce. Uses raw DOM so it can be
 * embedded inside an Editor.js block (which works with plain DOM nodes).
 */
export function renderPicture(
  media: MediaData,
  options: {
    lazy?: boolean;
    className?: string;
    breakpoints?: Record<number, number>;
  } = {},
): HTMLElement {
  const { lazy = true, className = '', breakpoints = DEFAULT_BREAKPOINTS } = options;

  if (media.type === 'video') {
    const video = document.createElement('video');
    video.src = media.url;
    video.controls = true;
    video.preload = lazy ? 'none' : 'metadata';
    if (className) video.className = className;
    return video;
  }

  const picture = document.createElement('picture');
  if (className) picture.className = className;

  const findThumb = (width: number, mime: string) =>
    media.thumbs.find((t) => t.width === width && t.mime === mime);

  // Sort breakpoints so "-1" (no upper bound) ends up last
  const entries = Object.entries(breakpoints)
    .map(([max, imgW]) => [Number(max), imgW] as const)
    .sort((a, b) => {
      const aKey = a[0] === -1 ? Infinity : a[0];
      const bKey = b[0] === -1 ? Infinity : b[0];
      return aKey - bKey;
    });

  let prevMax = 0;
  const singleBreakpoint = entries.length === 1;

  for (const [max, imgWidth] of entries) {
    for (const mime of ['image/avif', 'image/webp']) {
      const thumb1x = findThumb(imgWidth, mime);
      const thumb2x = findThumb(imgWidth * 2, mime);

      const parts: string[] = [];
      if (thumb1x) parts.push(`${thumb1x.url} 1x`);
      if (thumb2x) parts.push(`${thumb2x.url} 2x`);
      if (parts.length === 0) continue;

      const source = document.createElement('source');
      source.srcset = parts.join(', ');
      source.type = mime;

      if (!singleBreakpoint) {
        const clauses: string[] = [];
        if (prevMax > 0) clauses.push(`(width >= ${prevMax}px)`);
        if (max !== -1) clauses.push(`(width < ${max}px)`);
        if (clauses.length > 0) source.media = clauses.join(' and ');
      }

      picture.appendChild(source);
    }
    if (max !== -1) prevMax = max;
  }

  const img = document.createElement('img');
  img.src = media.url;
  if (media.width > 0) img.width = media.width;
  if (media.height > 0) img.height = media.height;
  img.alt = media.alt ?? '';
  if (lazy) img.loading = 'lazy';
  picture.appendChild(img);

  return picture;
}
