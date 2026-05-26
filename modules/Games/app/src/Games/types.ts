import type {EntityRow, Media, Nullable} from "@admin/types";

export interface GameRow extends EntityRow {
  title: string,
  releaseYear: Nullable<number>,
  cover: Nullable<Media>,
  materials: number
}
