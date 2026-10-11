import type {FieldDef} from "@admin/types";
import {type Game} from "./types";

const FIELDS: FieldDef<Game>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    role: "primary",
    span: "full",
    required: true
  },
  {
    name: "slug",
    type: "slug",
    label: "Слаг",
    required: true,
    role: "primary",
    span: "full",
    url: () => ""
  },
  {
    name: "cover",
    label: "Обложка",
    role: "primary",
    span: "full",
    type: "image",
    thumbnailWidth: 100,
    thumbnailHeight: 70,
    uploadPurpose: "game",
    required: true
  }
];

export default FIELDS;
