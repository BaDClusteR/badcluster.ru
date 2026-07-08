import {FactRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";


export default function Facts() {
  return <List<FactRow>
    name="facts"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Фан-факты",
      add: "Добавить факт",
      searchPlaceholder: "Поиск по тексту...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные факты ({{count}})?",
        single: row => <>Точно удалить факт <strong>#{row.id}</strong>?</>
      }
    }}
    webPath="facts"
    apiEndpoint="facts"
  />;
}
