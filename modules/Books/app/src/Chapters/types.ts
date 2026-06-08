import type {EntityRow} from "@admin/types";

export interface ChapterRow extends EntityRow {
  title: string,
  addedDate: string,
  updateDate: string,
  published: boolean
}
