import {Link, useParams} from "react-router";
import {type Fact} from "./types";
import fields from "./fields";
import getDataProvider from "./dataProvider";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";

export default function FactPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<Fact>
      fields={fields}
      dataProvider={getDataProvider(id)}
      initialValues={isCreateMode ? {title: "", content: ""} : undefined}
      webPath="facts"
      apiEndpoint="fact"
      title={() => <>
        <Link to={buildAdminUrl("facts")}>Фан-факты</Link> :: {isCreateMode ? "Новый факт" : `#${id}`}
      </>}
      labels={{
        notFound: {
          text: "Факт не найден",
          btnCaption: "К списку фактов"
        },
        submit: {
          create: "Добавить факт",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Факт успешно добавлен",
          onUpdate: "Факт успешно сохранен"
        }
      }}
    />
  );
}
