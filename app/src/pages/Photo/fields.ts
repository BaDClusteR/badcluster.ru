import type {FieldDef} from "@admin/types";
import {Photo, PhotoContext} from "./types";

const FIELDS: FieldDef<Photo, PhotoContext>[] = [
  {
    name: "image",
    label: "Картинка",
    type: "simpleImage",
    uploadPurpose: "photo",
    required: true,
    span: "full"
  },
  {
    name: "tags",
    label: "Тэги",
    type: "multiselect",
    placeholder: "Тэги",
    options: (context) => context?.tags,
    span: "full"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    hint: "Если не указывать, фотка встанет в начало"
  }
];

export default FIELDS;
