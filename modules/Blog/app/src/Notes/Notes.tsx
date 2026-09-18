import {useAdminCore} from "../admin/useAdminCore";
import {NoteRow} from "./types";
import getColumns from "./columns";

export default function Notes() {
  const {List, BadgeGreen, BadgeGray} = useAdminCore();

  return <List<NoteRow>
    name="notes"
    columns={getColumns(BadgeGreen, BadgeGray)}
    labels={{
      title: "Заметки",
      add: "Новая заметка",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные заметки ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath="blog/notes"
    apiEndpoint="notes"
  />;
}
