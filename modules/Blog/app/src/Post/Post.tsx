import {useParams} from "react-router";
import {useQuery} from "@tanstack/react-query";
import {useAdminCore} from "../admin/useAdminCore";
import {Post, TagApi, TagsApiCallResult, BlogPostContext} from "./types";
import fields from "./fields";

export default function BlogPost() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, apiCall, createEntityFormDataProvider} = useAdminCore();

  const isCreateMode = !id;

  const {data} = useQuery({
    queryKey: ["post_tags"],
    queryFn: ({signal}) => apiCall(
      "GET",
      "post_tags",
      {},
      {signal}
    )
  });

  const tagsRaw = data as TagsApiCallResult | undefined;

  const context: BlogPostContext = {
    tags: Array.isArray(tagsRaw?.tags)
      ? tagsRaw.tags.map(
        (t: TagApi) => ({
          value: String(t.id),
          label: t.title
        })
      )
      : []
  };

  return (
    <EntityForm<Post, BlogPostContext>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Post>("post", id, isCreateMode)}
      initialValues={isCreateMode ? {published: false} : undefined}
      context={context}
      webPath="post"
      apiEndpoint="post"
      labels={{
        notFound: {
          text: "Пост не найден",
          btnCaption: "Назад к постам"
        },
        submit: {
          create: "Создать пост",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Пост успешно создан",
          onUpdate: "Пост успешно сохранен"
        }
      }}
    />
  );
}
