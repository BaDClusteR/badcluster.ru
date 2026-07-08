import {CommentRow} from "./types";
import {List} from "@/components/List/List";
import columns from "./columns";


export default function Comments() {
  return <List<CommentRow>
    name="comments"
    columns={columns}
    permissions={{add: false, edit: true, delete: true, select: true, filter: true}}
    labels={{
      title: "Комментарии",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные комментарии ({{count}})?",
        single: row => <>Точно удалить коммент от <strong>{row.name} ({row.date})</strong>?</>
      }
    }}
    webPath="comments"
    apiEndpoint="comments"
  />;
}
