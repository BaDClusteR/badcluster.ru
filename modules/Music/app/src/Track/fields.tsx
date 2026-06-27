import {FieldDef} from "@admin/types";
import {type Track} from "./types";

const FIELDS: FieldDef<Track>[] = [
  {
    type: "text",
    name: "title",
    label: "Название",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    type: "file",
    name: "song",
    label: "Песня",
    required: true,
    span: "full",
    role: "primary",
    uploadEndpoint: "/admin/api/song_upload",
    accept: ".mp3",
    subtitle: file => file.duration ?? file.mime
  },
  {
    type: "textarea",
    name: "lyrics",
    label: "Слова",
    span: "full",
    role: "primary"
  },
  {
    type: "textarea",
    name: "annotation",
    label: "Аннотация",
    span: "full",
    role: "primary"
  },
  {
    type: "text",
    name: "sourceUrl",
    label: "Урла источника",
    hint: "Ссылка на песню на Suno.com",
    role: "primary"
  },
  {
    type: "text",
    name: "clipUrl",
    label: "Урла клипа",
    hint: "Embedded ссылка на клип на YT",
    role: "primary"
  },
  {
    type: "number",
    name: "position",
    label: "Позиция",
    role: "primary"
  },
  {
    type: "switch",
    name: "explicitLanguage",
    label: "Нецензурная лексика",
    role: "primary"
  }
];

export default FIELDS;
