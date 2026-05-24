import type {FieldDef} from "@/components/EntityForm";
import {Comment, CommentContext} from "../types";
import renderCommentInfo from "./commentInfo";

const FIELDS: FieldDef<Comment, CommentContext>[] = [
  {
    name: "date",
    label: "Дата",
    type: "datetime",
    role: "primary"
  },
  {
    name: "name",
    label: "Никнейм",
    type: "text",
    role: "primary"
  },
  {
    name: "comment",
    label: "Комментарий",
    type: "textarea",
    role: "primary",
    span: "full"
  },
  {
    name: "status",
    label: "Статус",
    type: "select",
    role: "primary",
    span: "full",
    options: [
      {
        value: "A",
        label: "Подтвержден"
      },
      {
        value: "M",
        label: "На модерации"
      },
      {
        value: "D",
        label: "Отклонен"
      }
    ]
  },
  {
    type: "group",
    role: "primary",
    span: "full",
    render: renderCommentInfo
  }
];

export default FIELDS;
