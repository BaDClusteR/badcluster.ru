import {ColumnDef} from "@admin/types";
import {TagRow} from "./types";

const columns: ColumnDef<TagRow>[] = [
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
    key: "count",
    header: "Кол-во постов",
    sortable: true
  }
];

export default columns;
