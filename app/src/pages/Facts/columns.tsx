import {ColumnDef} from "@admin/types";
import {FactRow} from "./types.ts";
import classes from "./Facts.module.css";

const columns: ColumnDef<FactRow>[] = [
  {
    key: "title",
    header: "Название",
    link: true,
    render: row => row.title || `#${row.id}`
  },
  {
    key: "content",
    header: "Текст",
    render: row => <span className={classes.content}>{row.content}</span>
  }
];

export default columns;
