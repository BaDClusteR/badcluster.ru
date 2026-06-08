import type {FieldDef} from "@admin/types";
import {Chapter, ChapterContext} from "../types";
import ChapterEditor from "./ChapterEditor";

const FIELDS: FieldDef<Chapter, ChapterContext>[] = [
  {
    type: "group",
    role: "primary",
    span: "full",
    render: (form, options) =>
      <ChapterEditor form={form} options={options}/>
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    required: true,
    placeholder: "url-friendly-name",
    url: (slug: string, values, context) => (
      context?.book?.slug
        ? `http://bc.local/books/${context.book.slug}/${slug}`
        : ""
    )
  },
  {
    name: "published",
    label: "Опубликована",
    type: "switch"
  },
  {
    name: "part",
    label: "Раздел",
    type: "text",
    span: "full"
  },
  {
    name: "addedDate",
    label: "Дата добавления",
    type: "datetime"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number"
  }
];

export default FIELDS;
