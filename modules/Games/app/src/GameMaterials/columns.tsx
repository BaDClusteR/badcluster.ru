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
    key: "game",
    header: "Игра",
    sortable: true,
    render: row => <Link to={`/admin/games/${row.game.id}`}>{row.game.title}</Link>
  },
  {
    key: "annotation",
    header: "Аннотация"
  },
  {
    key: "date",
    header: "Добавлен",
    sortable: true,
    nowrap: true
  }
];

export default columns;
