import {BadgeComponent, ColumnDef} from "@admin/types";
import {NoteRow} from "./types";

export default function getColumns(BadgeGreen: BadgeComponent, BadgeGray: BadgeComponent): ColumnDef<NoteRow>[] {
  return [
    {
      key: "title",
      header: "Название",
      sortable: true,
      link: true
    },
    {
      key: "slug",
      header: "Слаг",
      sortable: true,
      render: (row) => <code>{row.slug}</code>
    },
    {
      key: "published",
      header: "Статус",
      sortable: true,
      render: (row: NoteRow) => (
        row.published
          ? <BadgeGreen>Опубликована</BadgeGreen>
          : <BadgeGray>Драфт</BadgeGray>
      )
    },
    {
      key: "publish_date",
      header: "Дата публикации",
      sortable: true,
      width: 120
    }
  ];
}
