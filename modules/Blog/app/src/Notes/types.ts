import type {EntityRow} from "@admin/types";

export interface NoteRow extends EntityRow {
  title: string,
  slug: string,
  published: boolean,
  publish_date: string
}
