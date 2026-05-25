import type {FieldDef} from "@admin/types";
import {Tag} from "./types";

const FIELDS: FieldDef<Tag>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    span: "full",
    role: "primary",
    required: true
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    span: "full",
    role: "primary",
    required: true,
    placeholder: "url-friendly-name",
    url: (slug: string) => `http://bc.local/blog/tag/${slug}`,
    validate: (v) => /^[a-z0-9-]+$/.test(v as string)
      ? null
      : "Только латиница, цифры и дефис"
  },
  {
    name: "description",
    label: "Краткое описание",
    hint: "Будет под заголовком на первой странице тэга",
    type: "textarea",
    span: "full",
    role: "primary",
    required: true
  }
];

export default FIELDS;
