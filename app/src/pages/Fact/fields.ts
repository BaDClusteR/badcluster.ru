import type {FieldDef} from "@admin/types";
import {Fact} from "./types";

const FIELDS: FieldDef<Fact>[] = [
  {
    name: "content",
    label: "Текст",
    type: "textarea",
    placeholder: "Текст факта",
    required: true,
    span: "full",
    role: "primary"
  }
];

export default FIELDS;
