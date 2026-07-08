import type {FieldDef} from "@admin/types";
import {PulseItem} from "./types";

const FIELDS: FieldDef<PulseItem>[] = [
  {
    name: "image",
    label: "Картинка",
    type: "simpleImage",
    span: "full",
    role: "primary"
  },
  {
    name: "tag",
    label: "Тэг",
    type: "text",
    placeholder: "Ремастеринг",
    span: "full",
    role: "primary"
  },
  {
    name: "title",
    label: "Заголовок",
    type: "text",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "url",
    label: "Ссылка",
    type: "text",
    placeholder: "/art/doom3",
    span: "full",
    role: "primary"
  },
  {
    name: "text",
    label: "Текст",
    type: "text",
    span: "full",
    role: "primary"
  },
  {
    name: "statusTitle",
    label: "Заголовок статуса",
    type: "text",
    placeholder: "Сейчас в работе:",
    span: "full",
    role: "primary"
  },
  {
    name: "statusText",
    label: "Текст статуса",
    type: "text",
    span: "full",
    role: "primary"
  },
  {
    name: "icon",
    label: "Иконка",
    type: "text",
    placeholder: "📖",
    role: "primary"
  },
  {
    name: "position",
    label: "Позиция",
    type: "number",
    role: "primary"
  },
  {
    name: "isTall",
    label: "Высокий блок",
    type: "switch",
    hint: "Занимает два ряда сетки",
    role: "primary"
  },
  {
    name: "isSurfaced",
    label: "Блок с подложкой",
    type: "switch",
    hint: "Полупрозрачный фон-поверхность",
    role: "primary"
  }
];

export default FIELDS;
