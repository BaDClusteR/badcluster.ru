import {ColumnDef} from "@admin/types";
import {TrackRow} from "./types";

const columns: ColumnDef<TrackRow>[] = [
  {
    key: "title",
    header: "Название",
    link: true
  },
  {
    key: "duration",
    header: "Продолжительность"
  }
];

export default columns;
