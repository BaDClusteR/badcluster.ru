import {useAdminCore} from "../admin/useAdminCore";
import {AlbumRow} from "./types";
import columns from "./columns";

export default function Albums() {
  const {List} = useAdminCore();

  return <List<AlbumRow>
    name="albums"
    columns={columns}
    labels={{
      title: "Музыкальные сборники",
      add: "Добавить сборник",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные сборники ({{count}})? Удалятся также все их песни.",
        single: row => <>Точно удалить <strong>{row.title}</strong>?<br/>Все песни этого сборника также будут
          удалены.</>
      }
    }}
    webPath="music"
    apiEndpoint="albums"
  />;
}
