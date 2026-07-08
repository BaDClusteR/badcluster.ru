import type {EntityRow} from "@admin/types";

export interface PhotoTagRow extends EntityRow {
  title: string,
  photosCount: number
}
