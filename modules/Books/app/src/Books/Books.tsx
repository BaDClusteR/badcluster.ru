import {useAdminCore} from "../admin/useAdminCore";
import {BookRow} from "./types";
import columns from "./columns";

export default function Books() {
  const {List} = useAdminCore();

  return <List<BookRow>
    name="books"
    columns={columns}
    labels={{
      title: "Библиотека",
      add: "Новое произведение",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные произведения ({{count}})? Удалятся также все их главы.",
        single: row => <>Точно удалить <strong>{row.title}</strong>?<br/>Все главы произведения также будут удалены.</>
      }
    }}
    webPath="books"
    apiEndpoint="books"
  />;
}
