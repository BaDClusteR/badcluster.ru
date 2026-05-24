import {Link, useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {type Tag} from "./types";
import fields from "./fields";

export function Tag() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, buildAdminUrl, createEntityFormDataProvider} = useAdminCore();

  const isCreateMode = !id;

  return (
    <EntityForm<Tag>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Tag>("tag", id, isCreateMode)}
      webPath="blog/tags"
      apiEndpoint="tag"
      labels={{
        notFound: {
          text: "Тэг не найден",
          btnCaption: "Назад к тэгам"
        },
        submit: {
          create: "Создать тэг",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Тэг успешно создан",
          onUpdate: "Тэг успешно сохранен"
        }
      }}
      title={(values) => <>
        <Link to={buildAdminUrl("blog/tags")}>Тэги</Link> :: {
        isCreateMode
          ? "Новый тэг"
          : values?.title
      }
      </>}
    />
  );
}
