import {ColumnDef} from "@admin/types";
import {PhotoTagRow} from "./types.ts";

const columns: ColumnDef<PhotoTagRow>[] = [
  {
    key: "id",
    header: "ID",
    sortable: true,
    link: true,
    nowrap: true
  },
  {
    key: "title",
    header: "Название",
    sortable: true,
    link: true
  },
  {
    key: "photosCount",
    header: "Фотки",
    nowrap: true
  }
];

export default columns;
