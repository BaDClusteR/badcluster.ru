import type { FieldDef } from "@admin/types";
import {TagDetailed} from "./types";

const FIELDS: FieldDef<TagDetailed>[] = [
  {
    name: 'title',
    label: 'Название',
    type: 'text',
    span: 'full',
    required: true
  },
  {
    name: 'slug',
    label: 'Слаг',
    type: 'text',
    span: 'full',
    required: true,
    placeholder: 'url-friendly-name',
    validate: (v) => /^[a-z0-9-]+$/.test(v as string)
      ? null
      : 'Только латиница, цифры и дефис',
  }
];

export default FIELDS;
