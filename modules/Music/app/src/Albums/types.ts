import type {EntityRow, Media, Nullable} from "@admin/types";

export interface AlbumRow extends EntityRow {
  cover: Nullable<Media>,
  title: string,
  genre: string,
  type: string,
  releaseDate: string,
  tracks: string
}
