import type { MediaData } from './types';
export interface UploadProgress {
    /** Bytes uploaded so far. */
    loaded: number;
    /** Total bytes to upload (0 if unknown). */
    total: number;
    /** Fraction 0..1, or 0 if total is unknown. */
    fraction: number;
}
export interface UploadHandle {
    promise: Promise<MediaData>;
    abort: () => void;
}
/**
 * Uploads a single file via XHR so we get upload-progress events,
 * which fetch() still doesn't expose reliably across browsers.
 */
export declare function uploadMedia(file: File, onProgress?: (progress: UploadProgress) => void): UploadHandle;
