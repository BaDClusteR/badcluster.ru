import type {EntityRow} from "@admin/types";

export interface StaticPageRow extends EntityRow {
  title: string,
  slug: string,
  published: boolean,
  created_date: string
}
