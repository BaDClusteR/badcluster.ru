import {StaticPageRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";

export default function StaticPages() {
  return <List<StaticPageRow>
    name="static-pages"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Статичные страницы",
      add: "Добавить страницу",
      searchPlaceholder: "Поиск по заголовку и слагу...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные страницы ({{count}})?",
        single: row => <>Точно удалить страницу <strong>{row.title || `#${row.id}`}</strong>?</>
      }
    }}
    webPath="static-pages"
    apiEndpoint="static-pages"
  />;
}
