import type {FieldDef} from "@admin/types";
import {Fact} from "./types";

const FIELDS: FieldDef<Fact>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    placeholder: "Название факта",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "content",
    label: "Текст",
    type: "textarea",
    placeholder: "Текст факта",
    required: true,
    span: "full",
    role: "primary",
    softMaxLength: 450
  }
];

export default FIELDS;
