import type {FieldDef} from "@admin/types";
import {Album} from "./types";

const FIELDS: FieldDef<Album>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    role: "primary",
    span: "full",
    required: true
  },
  {
    name: "cover",
    label: "Обложка",
    type: "image",
    thumbnailWidth: 240,
    thumbnailHeight: 240,
    previewWidth: 240,
    span: "full",
    role: "primary",
    uploadPurpose: "album_cover"
  },
  {
    name: "type",
    label: "Тип",
    type: "select",
    role: "primary",
    required: true,
    options: [
      {value: "S", label: "Сингл"},
      {value: "D", label: "Double single"},
      {value: "E", label: "EP"},
      {value: "A", label: "Альбом"}
    ]
  },
  {
    name: "releaseDate",
    type: "datetime",
    label: "Дата релиза",
    role: "primary",
    required: true
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    role: "primary",
    span: "full",
    required: true,
    url: slug => `http://bc.local/music/${slug}`
  },
  {
    name: "genre",
    label: "Жанр",
    type: "text",
    role: "primary",
    span: "full",
    hint: "Можно перечислить через запятую."
  },
  {
    name: "shortAnnotation",
    label: "Краткая аннотация",
    type: "textarea",
    role: "primary",
    span: "full",
    required: true
  },
  {
    name: "annotation",
    label: "Аннотация",
    hint: "Если оставить поле пустым, на месте аннотации будет краткая аннотация.",
    type: "textarea",
    role: "primary",
    span: "full"
  },
  {
    name: "musicBy",
    label: "Music by",
    type: "text",
    role: "primary"
  },
  {
    name: "visualBy",
    label: "Visuals by",
    type: "text",
    role: "primary"
  },
  {
    name: "coverBy",
    label: "Cover by",
    type: "text",
    role: "primary"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    role: "primary"
  }
];

export default FIELDS;
