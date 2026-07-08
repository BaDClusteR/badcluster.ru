import {RedirectRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";


export default function Redirects() {
  return <List<RedirectRow>
    name="redirects"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Редиректы",
      add: "Добавить редирект",
      searchPlaceholder: "Поиск по путям...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные редиректы ({{count}})?",
        single: row => <>Точно удалить редирект <strong>{row.path}</strong>?</>
      }
    }}
    webPath="redirects"
    apiEndpoint="redirects"
  />;
}