import {Link, useParams} from "react-router";
import type {EntityFormDataProvider} from "@admin/types";
import {type Comment, CommentContext} from "./types";
import fields from "./fields";
import {EntityForm} from "@/components/EntityForm";
import {useState} from "react";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";
import getDataProvider from "@/pages/Comment/dataProvider.ts";

export default function Comment() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;
  const [context, setContext] = useState<CommentContext | undefined>(undefined);

  const dataProvider: EntityFormDataProvider<Comment> | undefined = isCreateMode
    ? undefined
    : getDataProvider(
      parseInt(id) || 0,
      setContext
    );

  return (
    <EntityForm<Comment, CommentContext>
      fields={fields}
      dataProvider={dataProvider}
      context={context}
      webPath="comments"
      apiEndpoint="comment"
      title={(_value, context) => <>
        <Link to={buildAdminUrl("comments")}>Комменты</Link> :: {context?.name} ({context?.dateHumanReadable})
      </>}
      labels={{
        notFound: {
          text: "Комментарий не найден",
          btnCaption: "К списку комментов"
        },
        submit: {
          update: "Сохранить"
        },
        messages: {
          onUpdate: "Комментарий успешно сохранен"
        }
      }}
    />
  );
}
