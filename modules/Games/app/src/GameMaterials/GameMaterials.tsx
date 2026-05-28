import {useAdminCore} from "../admin/useAdminCore";
import {GameMaterialRow} from "./types";
import columns from "./columns";

export default function GameMaterials() {
  const {List} = useAdminCore();

  return <List<GameMaterialRow>
    name="materials"
    columns={columns}
    labels={{
      title: "Игровые материалы",
      add: "Добавить материал",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные материалы ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath="games/materials"
    apiEndpoint="materials"
  />;
}
