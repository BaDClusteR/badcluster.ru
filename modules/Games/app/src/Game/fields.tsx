import type {FieldDef} from "@admin/types";
import {type Game} from "./types";

const years = [
  {value: "", label: "Не задан"}
];

for (let i = new Date().getFullYear(); i >= 1980; i--) {
  const str = i.toString();

  years.push(
    {value: str, label: str}
  );
}

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
    url: () => ""
  },
  {
    name: "releaseYear",
    label: "Год выхода",
    role: "primary",
    type: "select",
    options: years
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
