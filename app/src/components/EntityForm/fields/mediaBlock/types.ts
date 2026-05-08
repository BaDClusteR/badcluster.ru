export interface MediaThumb {
  width: number;
  height: number;
  mime: string;
  url: string;
}

export type MediaType = 'image' | 'video';

/** Shape of the Media API response and of the persisted block data. */
export interface MediaData {
  id: number;
  url: string;
  width: number;
  height: number;
  mime: string;
  alt: string;
  type: MediaType;
  thumbs: MediaThumb[];
}

/** Saved Editor.js block payload. */
export interface MediaBlockData {
  media?: MediaData;
  lazy: boolean;
  caption?: string;
}

/** Saved Editor.js block payload for gallery/slideshow. */
export interface GalleryBlockData {
  slides: MediaData[];
  captions: string[];
  lazy: boolean;
}
