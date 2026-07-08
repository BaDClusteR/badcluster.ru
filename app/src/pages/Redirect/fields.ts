import type {FieldDef} from "@admin/types";
import {Redirect} from "./types";

const FIELDS: FieldDef<Redirect>[] = [
  {
    name: "path",
    label: "Откуда",
    type: "text",
    placeholder: "/old-page",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "code",
    label: "Код возврата",
    type: "select",
    options: [
      {value: "301", label: "301 — постоянный редирект"},
      {value: "410", label: "410 — страница удалена"}
    ],
    required: true,
    role: "primary"
  },
  {
    name: "destination",
    label: "Куда",
    type: "text",
    placeholder: "/new-page",
    required: true,
    span: "full",
    role: "primary",
    visible: values => values.code !== "410"
  }
];

export default FIELDS;