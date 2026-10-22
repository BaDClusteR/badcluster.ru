import {ColumnDef} from "@admin/types";
import {LogRecordRow} from "./types";
import LevelBadge from "./LevelBadge";
import classes from "./LogRecords.module.css";

// «2 раза», но «5 раз» и «12 раз»
const timesWord = (count: number): string =>
  count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 12 || count % 100 > 14) ? "раза" : "раз";

const columns: ColumnDef<LogRecordRow>[] = [
  {
    key: "message",
    header: "Сообщение",
    link: true,
    render: row => <span className={classes.message}>{row.message}</span>
  },
  {
    key: "date",
    header: "Когда",
    nowrap: true,
    // Повторы одной записи не плодят строки, а копятся в счётчике
    subRender: row => row.occurrences > 1 ? `повторилась ${row.occurrences} ${timesWord(row.occurrences)}` : null
  },
  {
    key: "level",
    header: "Уровень",
    sortable: true,
    render: row => <LevelBadge level={row.level}/>
  }
];

export default columns;
