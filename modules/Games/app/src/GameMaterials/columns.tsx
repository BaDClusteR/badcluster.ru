import {ColumnDef} from "@admin/types";
import {GameMaterialRow} from "./types";
import {Link} from "react-router";

const columns: ColumnDef<GameMaterialRow>[] = [
  {
    key: "title",
    header: "Название",
    link: true
  },
  {
    key: "game_title",
    header: "Игра",
    sortable: true,
    nowrap: true,
    render: row => <Link to={`/admin/games/${row.game.id}`}>{row.game.title}</Link>
  },
  {
    key: "annotation",
    header: "Аннотация"
  },
  {
    key: "date_added",
    header: "Добавлен",
    sortable: true,
    nowrap: true
  }
];

export default columns;
