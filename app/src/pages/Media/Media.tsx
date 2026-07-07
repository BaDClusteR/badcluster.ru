import {useState} from "react";
import {Link, useParams} from "react-router";
import type {EntityFormDataProvider} from "@admin/types";
import {MediaContext, MediaFile} from "./types";
import fields from "./fields";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";
import getDataProvider from "./dataProvider";

export default function MediaPage() {
  const {id} = useParams<{ id: string }>();
  const [context, setContext] = useState<MediaContext | undefined>(undefined);

  const dataProvider: EntityFormDataProvider<MediaFile> | undefined = id
    ? getDataProvider(parseInt(id) || 0, setContext)
    : undefined;

  return (
    <EntityForm<MediaFile, MediaContext>
      fields={fields}
      dataProvider={dataProvider}
      context={context}
      webPath="media"
      apiEndpoint="media_file"
      title={(_values, context) => <>
        <Link to={buildAdminUrl("media")}>Медиатека</Link> :: {context?.filename ?? `#${id}`}
      </>}
      preprocessValues={(values) => ({
        width: Number(values.width) || 0,
        height: Number(values.height) || 0,
        alt: values.alt
      })}
      labels={{
        notFound: {
          text: "Файл не найден",
          btnCaption: "К медиатеке"
        },
        submit: {
          update: "Сохранить"
        },
        messages: {
          onUpdate: "Файл успешно сохранен"
        }
      }}
    />
  );
}