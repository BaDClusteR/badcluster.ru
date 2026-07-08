import {Link, useParams} from "react-router";
import {type PhotoTag} from "./types";
import fields from "./fields";
import getDataProvider from "./dataProvider";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";

export default function PhotoTagPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<PhotoTag>
      fields={fields}
      dataProvider={getDataProvider(id)}
      initialValues={isCreateMode ? {title: "", position: ""} : undefined}
      webPath="photo-tags"
      apiEndpoint="photo_tag"
      preprocessValues={(values) => ({
        title: values.title,
        position: Number(values.position) || 0
      })}
      title={() => <>
        <Link to={buildAdminUrl("photo-tags")}>Тэги фоток</Link> :: {isCreateMode ? "Новый тэг" : `#${id}`}
      </>}
      labels={{
        notFound: {
          text: "Тэг не найден",
          btnCaption: "К списку тэгов"
        },
        submit: {
          create: "Добавить тэг",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Тэг успешно добавлен",
          onUpdate: "Тэг успешно сохранен"
        }
      }}
    />
  );
}
