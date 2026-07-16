import {ColumnDef} from "@admin/types";
import {PhotoTagRow} from "./types.ts";

const columns: ColumnDef<PhotoTagRow>[] = [
  {
    key: "title",
    header: "Название",
    sortable: true,
    link: true
  },
  {
    key: "photosCount",
    header: "Фоток с тэгом",
    nowrap: true
  },
  {
    key: "id",
    header: "ID",
    sortable: true,
    nowrap: true
  }
];

export default columns;
