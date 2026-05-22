import type {EntityRow} from "@admin/types";

export interface CommentRow extends EntityRow {
  date: string,
  name: string,
  comment: string,
  status: string,
  page: string,
  pageLink: string
}
