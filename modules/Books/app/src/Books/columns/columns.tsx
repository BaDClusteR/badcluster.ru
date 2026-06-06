import {ColumnDef} from "@admin/types";
import {BookRow} from "../types";
import BookCover from "./BookCover";
import BookTypeBadge from "./BookTypeBadge";

const columns: ColumnDef<BookRow>[] = [
  {
    key: "cover",
    header: "Обложка",
    render: row => <BookCover media={row.cover}/>
  },
  {
    key: "title",
    header: "Название",
    sortable: true,
    link: true
  },
  {
    key: "shortAnnotation",
    header: "Краткая аннотация"
  },
  {
    key: "type",
    header: "Тип произведения",
    render: row => <BookTypeBadge type={row.type}/>
  },
  {
    key: "lastUpdateDate",
    header: "Обновлен",
    sortable: true
  }
];

export default columns;
