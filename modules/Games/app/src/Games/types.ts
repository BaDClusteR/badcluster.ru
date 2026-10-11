import type {EntityRow, Media, Nullable} from "@admin/types";

export interface GameRow extends EntityRow {
  title: string,
  cover: Nullable<Media>,
  count: number
}
