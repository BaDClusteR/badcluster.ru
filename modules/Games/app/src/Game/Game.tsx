import {Link, useParams} from "react-router";
import type {EntityFormDataProvider} from "@admin/types";
import fields from "./fields";
import {useAdminCore} from "../admin/useAdminCore";
import {type Game} from "./types";

export default function Game() {
  const {createEntityFormDataProvider, EntityForm, buildAdminUrl} = useAdminCore();

  const {id} = useParams<{ id: string }>();
  const isCreateMode = !id;

  return (
    <EntityForm<Game>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Game>("game", id, isCreateMode)}
      webPath="games"
      apiEndpoint="game"
      title={(value) => <>
        <Link to={buildAdminUrl("games")}>Игры</Link> :: {value?.title}
      </>}
      labels={{
        notFound: {
          text: "Игра не найдена",
          btnCaption: "К списку игр"
        },
        submit: {
          create: "Добавить",
          update: "Сохранить"
        },
        messages: {
          onUpdate: "Изменения сохранены",
          onCreate: "Игра успешно добавлена"
        }
      }}
    />
  );
}
