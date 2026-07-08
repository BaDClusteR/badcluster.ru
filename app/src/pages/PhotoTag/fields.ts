import type {FieldDef} from "@admin/types";
import {PhotoTag} from "./types";

const FIELDS: FieldDef<PhotoTag>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    placeholder: "Название тэга",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    hint: "Если не указывать, тэг встанет в конец списка",
    role: "primary"
  }
];

export default FIELDS;
