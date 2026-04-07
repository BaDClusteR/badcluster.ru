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
export function uploadMedia(
  file: File,
  onProgress?: (progress: UploadProgress) => void,
): UploadHandle {
  const xhr = new XMLHttpRequest();

  const promise = new Promise<MediaData>((resolve, reject) => {
    xhr.open('POST', '/api/admin/media/upload');
    xhr.responseType = 'json';

    xhr.upload.addEventListener('progress', (event) => {
      if (!onProgress) return;
      const total = event.lengthComputable ? event.total : 0;
      const fraction = total > 0 ? event.loaded / total : 0;
      onProgress({ loaded: event.loaded, total, fraction });
    });

    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(xhr.response as MediaData);
      } else {
        const msg =
          (xhr.response && typeof xhr.response === 'object' && 'error' in xhr.response
            ? String((xhr.response as { error: unknown }).error)
            : null) ?? `Upload failed with status ${xhr.status}`;
        reject(new Error(msg));
      }
    });

    xhr.addEventListener('error', () => reject(new Error('Network error during upload')));
    xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

    const form = new FormData();
    form.append('file', file);
    xhr.send(form);
  });

  return { promise, abort: () => xhr.abort() };
}
