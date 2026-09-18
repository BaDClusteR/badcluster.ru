import {ColumnDef} from "@admin/types";
import {BadgeGray, BadgeGreen} from "@/components/primitives/Badge";
import {StaticPageRow} from "./types.ts";

const columns: ColumnDef<StaticPageRow>[] = [
  {
    key: "title",
    header: "Заголовок",
    sortable: true,
    link: true,
    render: row => <>{row.title || `#${row.id}`}</>
  },
  {
    key: "slug",
    header: "Слаг",
    sortable: true,
    render: row => <code>/{row.slug}</code>
  },
  {
    key: "published",
    header: "Статус",
    sortable: true,
    nowrap: true,
    render: row => (
      row.published
        ? <BadgeGreen>Опубликована</BadgeGreen>
        : <BadgeGray>Драфт</BadgeGray>
    )
  },
  {
    key: "created_date",
    header: "Создана",
    sortable: true,
    nowrap: true,
    render: row => <>{row.created_date}</>
  }
];

export default columns;
