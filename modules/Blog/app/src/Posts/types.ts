import type {EntityRow} from "@admin/types";

export interface PostRow extends EntityRow {
  title: string,
  slug: string,
  published: boolean,
  publish_date: string,
  updateDate: string
}
