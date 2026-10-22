import {LogRecordRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";

/**
 * Журнал предупреждений и ошибок из лога. Разобранные записи удаляются —
 * пока журнал не пуст, виджет «Требует внимания» на дашборде об этом напоминает.
 */
export default function LogRecords() {
  return <List<LogRecordRow>
    name="log-records"
    columns={columns}
    permissions={{add: false, edit: false, delete: true, select: true, filter: true}}
    labels={{
      title: "Журнал ошибок",
      searchPlaceholder: "Поиск по сообщениям...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные записи ({{count}})?",
        single: row => <>Точно удалить запись от <strong>{row.date}</strong>?</>
      }
    }}
    webPath="logs"
    apiEndpoint="log-records"
  />;
}
