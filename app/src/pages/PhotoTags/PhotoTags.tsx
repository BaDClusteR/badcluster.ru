import {PhotoTagRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";


export default function PhotoTags() {
  return <List<PhotoTagRow>
    name="photo-tags"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Тэги фоток",
      add: "Добавить тэг",
      searchPlaceholder: "Поиск по названию...",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные тэги ({{count}})?",
        single: row => <>Точно удалить тэг <strong>{row.title}</strong>?</>
      }
    }}
    webPath="photo-tags"
    apiEndpoint="photo_tags"
  />;
}
