import {Link, useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {type Album} from "./types";
import fields from "./fields";

export default function Album() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, buildAdminUrl, createEntityFormDataProvider} = useAdminCore();

  const isCreateMode = !id;

  return (
    <EntityForm<Album>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Album>("album", id, isCreateMode)}
      webPath="music"
      apiEndpoint="album"
      labels={{
        notFound: {
          text: "Сборник не найден",
          btnCaption: "Назад к списку"
        },
        submit: {
          create: "Добавить",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Сборник добавлен",
          onUpdate: "Изменения сохранены"
        }
      }}
      title={(values) => <>
        <Link to={buildAdminUrl("music")}>Музыка</Link> :: {
        isCreateMode
          ? "Новый сборник"
          : values?.title ?? "[Безымянный сборник]"
      }
      </>}
    />
  );
}
