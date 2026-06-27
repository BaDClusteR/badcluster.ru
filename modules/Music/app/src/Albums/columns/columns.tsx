import {ColumnDef} from "@admin/types";
import {AlbumRow} from "../types";
import AlbumCover from "./AlbumCover";

const columns: ColumnDef<AlbumRow>[] = [
  {
    key: "cover",
    header: "Обложка",
    render: row => <AlbumCover media={row.cover}/>
  },
  {
    key: "title",
    header: "Название",
    sortable: true,
    link: true
  },
  {
    key: "releaseDate",
    header: "Дата релиза",
    sortable: true
  },
  {
    key: "type",
    header: "Тип",
    subKey: "genre"
  },
  {
    key: "tracks",
    header: "Песни",
    nowrap: true,
    link: row => `/admin/music/${row.id}/tracks`
  }
];

export default columns;
