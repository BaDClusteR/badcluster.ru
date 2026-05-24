import {useAdminCore} from "../admin/useAdminCore";
import {TagRow} from "./types";
import columns from "./columns";

export default function Tags() {
  const {List} = useAdminCore();

  return <List<TagRow>
    name="tags"
    columns={columns}
    labels={{
      title: "Тэги",
      add: "Новый тэг",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные тэги ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath="blog/tags"
    apiEndpoint="tags"
  />;
}
