import {useAdminCore} from "../admin/useAdminCore";
import {GameRow} from "./types";
import columns from "./columns";

export default function Games() {
  const {List} = useAdminCore();

  return <List<GameRow>
    name="games"
    columns={columns}
    labels={{
      title: "Игры",
      add: "Добавить игру",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные игры ({{count}})? Удалятся также все их материалы!",
        single: row => <>Точно удалить <strong>{row.title}</strong>?<br/>Все материалы игры также будут удалены!</>
      }
    }}
    webPath="games"
    apiEndpoint="games"
  />;
}
