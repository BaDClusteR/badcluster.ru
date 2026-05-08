import type { MediaData } from './types';
/**
 * Default breakpoints mapping viewport `maxWidth` → image width to use.
 * Matches the PHP Picture widget defaults (see BC\Widget\Common\Picture).
 * Use -1 as a key for "no upper bound".
 */
export declare const DEFAULT_BREAKPOINTS: Record<number, number>;
/**
 * Builds a native <picture> element (or <video> for videos) matching the
 * HTML the backend Picture widget would produce. Uses raw DOM so it can be
 * embedded inside an Editor.js block (which works with plain DOM nodes).
 */
export declare function renderPicture(media: MediaData, options?: {
    lazy?: boolean;
    className?: string;
    breakpoints?: Record<number, number>;
}): HTMLElement;
