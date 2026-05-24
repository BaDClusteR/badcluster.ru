import {BadgeComponent, ColumnDef} from "@admin/types";
import {PostRow} from "./types";

export default function getColumns(BadgeGreen: BadgeComponent, BadgeGray: BadgeComponent): ColumnDef<PostRow>[] {
  return [
    {
      key: "title",
      header: "Заголовок",
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
      key: "publish",
      header: "Статус",
      sortable: true,
      render: (row: PostRow) => (
        row.published
          ? <BadgeGreen>Опубликован</BadgeGreen>
          : <BadgeGray>Драфт</BadgeGray>
      )
    },
    {
      key: "publish_date",
      header: "Дата публикации",
      sortable: true,
      width: 120,
      subRender: row => row.updateDate
        ? `Обновлен ${row.updateDate}`
        : ""
    }
  ];
}
