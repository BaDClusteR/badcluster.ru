import {Link, useParams} from "react-router";
import {type Screenshot} from "./types";
import fields from "./fields";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";
import {createEntityFormDataProvider} from "@/utils/createDataProvider";

export default function ScreenshotPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<Screenshot>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Screenshot>("screenshot", id, isCreateMode)}
      initialValues={isCreateMode ? {image: null, alt: "", position: ""} : undefined}
      webPath="screenshots"
      apiEndpoint="screenshot"
      title={() => <>
        <Link to={buildAdminUrl("screenshots")}>Скриншоты</Link> :: {isCreateMode ? "Новый скриншот" : `#${id}`}
      </>}
      preprocessValues={(values) => ({
        image: values.image ? {id: values.image.id} : null,
        alt: values.alt,
        position: Number(values.position) || 0
      })}
      labels={{
        notFound: {
          text: "Скриншот не найден",
          btnCaption: "К списку скриншотов"
        },
        submit: {
          create: "Добавить скриншот",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Скриншот успешно добавлен",
          onUpdate: "Скриншот успешно сохранен"
        }
      }}
    />
  );
}