import type {FieldDef} from "@admin/types";
import {Screenshot} from "./types";

const FIELDS: FieldDef<Screenshot>[] = [
  {
    name: "image",
    label: "Картинка",
    type: "simpleImage",
    uploadPurpose: "screenshot",
    required: true,
    span: "full"
  },
  {
    name: "alt",
    label: "Подпись",
    type: "text",
    span: "full"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    hint: "Если не указывать, скриншот встанет в начало"
  }
];

export default FIELDS;
