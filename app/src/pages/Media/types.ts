export interface MediaFile {
  width: number | string;
  height: number | string;
  alt: string;
}

export interface MediaThumb {
  id: number;
  filename: string;
  url: string;
  mime: string;
  width: number;
  height: number;
  sizeHumanReadable: string;
}

export interface MediaContext {
  url: string;
  filename: string;
  mime: string;
  thumbs: MediaThumb[];
}

export type MediaApiCallResult = MediaFile & MediaContext;
