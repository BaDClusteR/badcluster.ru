import { useNavigate, useParams } from "react-router";
import { useQuery } from '@tanstack/react-query';
import { useAdminCore } from '../admin/useAdminCore';
import type {EntityCreatedResponse, EntityFormDataProvider} from "@admin/types";
import { PostDetailed, TagApi, TagsApiCallResult } from "./types";
import fields, {BlogPostContext} from "./fields";

export function BlogPost() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { EntityForm, apiCall, notify } = useAdminCore();

  const isCreateMode = !id;

  const { data } = useQuery({
    queryKey: ['tags'],
    queryFn: ({ signal }) => apiCall('GET', 'tags', {}, { signal }),
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
      : [],
  };

  const dataProvider: EntityFormDataProvider<PostDetailed> | undefined = isCreateMode
    ? undefined
    : {
        queryKey: ['post', id],
        getData: async (signal) => {
          return await apiCall('GET', `post/${id}`, {}, { signal }) as PostDetailed;
        }
      };

  return (
    <EntityForm<PostDetailed, BlogPostContext>
      fields={fields}
      dataProvider={dataProvider}
      initialValues={isCreateMode ? { published: false } : undefined}
      context={context}
      onSubmit={async (values: PostDetailed) => {
        if (isCreateMode) {
          const result = await apiCall('POST', 'post', values);
          notify.success("Создано", "Пост успешно создан");
          return result;
        } else {
          await apiCall('PUT', `post/${id}`, values);
          notify.success("Сохранено", "Пост успешно сохранен");
        }
      }}
      onCreated={(result: EntityCreatedResponse) => {
        if (result?.id) {
          navigate(`/admin/blog/${result.id}`, { replace: true });
        }
      }}
      notFoundText="Пост не найден"
      notFoundBtnCaption="Назад к постам"
      submitLabel={
        isCreateMode
          ? "Создать пост"
          : "Сохранить"
      }
    />
  );
}
