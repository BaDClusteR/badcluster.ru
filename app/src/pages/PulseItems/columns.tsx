import {ColumnDef} from "@admin/types";
import {PulseItemRow} from "./types.ts";
import classes from "./PulseItems.module.css";

const columns: ColumnDef<PulseItemRow>[] = [
  {
    key: "image",
    header: "Картинка",
    nowrap: true,
    render: row => row.image
      ? <img src={row.image} alt={`#${row.id}`} className={classes.image}/>
      : "—"
  },
  {
    key: "title",
    header: "Заголовок",
    sortable: true,
    link: true
  },
  {
    key: "text",
    header: "Текст",
    render: row => <span className={classes.text}>{row.text}</span>
  },
  {
    key: "position",
    header: "Позиция",
    sortable: true,
    nowrap: true
  }
];

export default columns;
