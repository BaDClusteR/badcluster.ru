import {Link, useParams} from "react-router";
import {type Redirect} from "./types";
import fields from "./fields";
import getDataProvider from "./dataProvider";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";

export default function RedirectPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<Redirect>
      fields={fields}
      dataProvider={getDataProvider(id)}
      initialValues={isCreateMode ? {path: "", code: "301", destination: ""} : undefined}
      webPath="redirects"
      apiEndpoint="redirect"
      title={() => <>
        <Link to={buildAdminUrl("redirects")}>Редиректы</Link> :: {isCreateMode ? "Новый редирект" : `#${id}`}
      </>}
      preprocessValues={(values) => ({
        path: values.path,
        code: Number(values.code),
        destination: values.code === "410" ? "" : values.destination
      })}
      labels={{
        notFound: {
          text: "Редирект не найден",
          btnCaption: "К списку редиректов"
        },
        submit: {
          create: "Добавить редирект",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Редирект успешно добавлен",
          onUpdate: "Редирект успешно сохранен"
        }
      }}
    />
  );
}