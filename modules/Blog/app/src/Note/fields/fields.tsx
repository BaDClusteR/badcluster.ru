import type {FieldDef} from "@admin/types";
import {Note} from "../types";
import NoteEditor from "./NoteEditor";

const FIELDS: FieldDef<Note>[] = [
  {
    type: "group",
    role: "primary",
    span: "full",
    render: (form, options) =>
      <NoteEditor form={form} options={options}/>
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    required: true,
    placeholder: "url-friendly-name",
    url: (slug: string) => `/notes/${slug}`
  },
  {
    name: "published",
    label: "Опубликована",
    type: "switch"
  },
  {
    name: "publishDate",
    label: "Дата публикации",
    type: "datetime",
    required: true
  },
  {
    name: "metaDescription",
    label: "Meta description",
    type: "text",
    span: "full",
    hint: "Краткое описание для поисковых систем",
    softMaxLength: 160
  }
];

export default FIELDS;
