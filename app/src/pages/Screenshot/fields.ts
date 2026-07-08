import type {FieldDef} from "@admin/types";
import {Screenshot} from "./types";

const FIELDS: FieldDef<Screenshot>[] = [
  {
    name: "image",
    label: "Картинка",
    type: "simpleImage",
    uploadPurpose: "screenshot",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "alt",
    label: "Подпись",
    type: "text",
    span: "full",
    role: "primary"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    hint: "Если не указывать, скриншот встанет в начало",
    role: "primary"
  }
];

export default FIELDS;
