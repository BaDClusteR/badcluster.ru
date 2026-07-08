import {ColumnDef} from "@admin/types";
import {RedirectRow} from "./types.ts";

const columns: ColumnDef<RedirectRow>[] = [
  {
    key: "path",
    header: "Откуда",
    sortable: true,
    link: true
  },
  {
    key: "code",
    header: "Код",
    sortable: true,
    nowrap: true
  },
  {
    key: "destination",
    header: "Куда",
    render: row => row.destination || "—"
  }
];

export default columns;