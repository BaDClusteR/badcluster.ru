import {File} from "@admin/types";

export interface Track {
  title: string,
  explicitLanguage: boolean,
  sourceUrl: string,
  lyrics: string,
  clipUrl: string,
  position: number,
  annotation: string,
  song: Song,
  albumId: number
}

export interface Song extends File {
  duration: string;
}

export interface TrackContext {
  albumName: string;
}
