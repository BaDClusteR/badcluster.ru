import {type Book} from "../Book/types";
import {Optional} from "@admin/types";

export interface Chapter {
  title: string,
  content: Record<string, unknown>[],
  position: number,
  published: boolean,
  addedDate: string,
  slug: string,
  part: string
}

export interface ChapterContext {
  book: Optional<Book>;
}
