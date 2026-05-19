import type {EntityRow} from "@admin/types";

export interface TagRow extends EntityRow {
  title: string,
  slug: string,
  count: number
}
