import type {EntityRow, Media, Nullable} from "@admin/types";

export interface BookRow extends EntityRow {
  cover: Nullable<Media>,
  title: string,
  shortAnnotation: string,
  type: "A" | "T",
  lastUpdateDate: string,
  chapterCount: string,
}
