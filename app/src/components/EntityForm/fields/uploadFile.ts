export interface FileUploadProgress {
  loaded: number;
  total: number;
  fraction: number;
}

export interface FileUploadHandle<T = any> {
  promise: Promise<T>;
  abort: () => void;
}

/**
 * Uploads a file via XHR with progress tracking.
 * Returns whatever JSON the backend responds with.
 */
export function uploadFile<T = any>(
  file: File,
  endpoint: string,
  onProgress?: (progress: FileUploadProgress) => void,
  extraFields?: Record<string, string>,
): FileUploadHandle<T> {
  const xhr = new XMLHttpRequest();

  const promise = new Promise<T>((resolve, reject) => {
    xhr.open("POST", endpoint);
    xhr.responseType = "json";

    xhr.upload.addEventListener("progress", (event) => {
      if (!onProgress) return;
      const total = event.lengthComputable ? event.total : 0;
      const fraction = total > 0 ? event.loaded / total : 0;
      onProgress({loaded: event.loaded, total, fraction});
    });

    xhr.addEventListener("load", () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(xhr.response as T);
      } else {
        const msg =
          (xhr.response && typeof xhr.response === "object" && "error" in xhr.response
            ? String((xhr.response as { error: unknown }).error)
            : null) ?? `Upload failed with status ${xhr.status}`;
        reject(new Error(msg));
      }
    });

    xhr.addEventListener("error", () => reject(new Error("Network error during upload")));
    xhr.addEventListener("abort", () => reject(new Error("Upload aborted")));

    const form = new FormData();
    form.append("file", file);
    if (extraFields) {
      for (const [key, val] of Object.entries(extraFields)) {
        form.append(key, val);
      }
    }
    xhr.send(form);
  });

  return {promise, abort: () => xhr.abort()};
}