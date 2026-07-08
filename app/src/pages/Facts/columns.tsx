import {ColumnDef} from "@admin/types";
import {FactRow} from "./types.ts";
import classes from "./Facts.module.css";

const columns: ColumnDef<FactRow>[] = [
  {
    key: "id",
    header: "ID",
    sortable: true,
    link: true,
    nowrap: true
  },
  {
    key: "content",
    header: "Текст",
    render: row => <span className={classes.content}>{row.content}</span>
  }
];

export default columns;
