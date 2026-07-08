import {Link, useParams} from "react-router";
import {type PulseItem} from "./types";
import fields from "./fields";
import getDataProvider from "./dataProvider";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";

export default function PulseItemPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<PulseItem>
      fields={fields}
      dataProvider={getDataProvider(id)}
      initialValues={isCreateMode
        ? {
          image: null,
          tag: "",
          title: "",
          url: "",
          text: "",
          statusTitle: "",
          statusText: "",
          icon: "",
          position: "",
          isTall: false,
          isSurfaced: false
        }
        : undefined}
      webPath="pulse_item"
      apiEndpoint="pulse_item"
      title={() => <>
        <Link to={buildAdminUrl("pulse")}>Пульс</Link> :: {isCreateMode ? "Новый элемент" : `#${id}`}
      </>}
      preprocessValues={(values) => ({
        image: values.image ? {id: values.image.id} : null,
        tag: values.tag,
        title: values.title,
        url: values.url,
        text: values.text,
        statusTitle: values.statusTitle,
        statusText: values.statusText,
        icon: values.icon,
        position: Number(values.position) || 0,
        isTall: values.isTall,
        isSurfaced: values.isSurfaced
      })}
      labels={{
        notFound: {
          text: "Элемент пульса не найден",
          btnCaption: "К списку"
        },
        submit: {
          create: "Добавить элемент",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Элемент пульса успешно добавлен",
          onUpdate: "Элемент пульса успешно сохранен"
        }
      }}
    />
  );
}
