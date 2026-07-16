import {PulseItemRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";


export default function PulseItems() {
  return <List<PulseItemRow>
    name="pulse"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Пульс",
      add: "Добавить элемент",
      searchPlaceholder: "Поиск по заголовкам и текстам...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные элементы ({{count}})?",
        single: row => <>Точно удалить элемент <strong>{row.title}</strong>?</>
      }
    }}
    webPath="pulse_item"
    apiEndpoint="pulse_items"
    isRowReadonly={row => row.isAuto}
  />;
}
