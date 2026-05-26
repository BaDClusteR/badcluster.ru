import {ColumnDef} from "@admin/types";
import {GameRow} from "../types";
import GameCover from "./GameCover";

const columns: ColumnDef<GameRow>[] = [
  {
    key: "cover",
    header: "Обложка",
    render: row => <GameCover media={row.cover}/>
  },
  {
    key: "title",
    header: "Название",
    sortable: true,
    link: true,
    subKey: "releaseYear"
  },
  {
    key: "count",
    header: "Материалы",
    sortable: true
  }
];

export default columns;
