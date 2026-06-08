import {ColumnDef} from "@admin/types";
import {ChapterRow} from "../types";
import ChapterStatus from "./ChapterStatus";

const columns: ColumnDef<ChapterRow>[] = [
  {
    key: "title",
    header: "Заголовок",
    link: true
  },
  {
    key: "addedDate",
    header: "Добавлена",
    sortable: true
  },
  {
    key: "updateDate",
    header: "Обновлена",
    sortable: true
  },
  {
    key: "published",
    header: "Статус",
    render: row => <ChapterStatus published={row.published}/>
  }
];

export default columns;
