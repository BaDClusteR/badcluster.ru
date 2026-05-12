import { useNavigate, useParams } from "react-router";
import { MultiSelect, Skeleton, Textarea } from "@mantine/core";
import { useQuery } from '@tanstack/react-query';
import { useAdminCore } from '../admin/useAdminCore';
import type { EntityFormDataProvider, FieldDef } from "@admin/types";
import classes from "./styles.module.css";
import { PostDetailed, TagApi, TagsApiCallResult } from "./types";

interface BlogPostContext {
  tags: { value: string; label: string }[];
}

const FIELDS: FieldDef<PostDetailed, BlogPostContext>[] = [
  {
    type: 'group',
    role: 'primary',
    span: "full",
    render: (form, options) => {
      const tags = options?.context?.tags;
      const { BlocksField, FieldGroup } = options!.components;
      const titleProps = form.getInputProps('title');
      return <>
        <FieldGroup isSubmitting={form.submitting}>
          <Skeleton visible={options?.loading}>
            <Textarea
              autosize
              placeholder="Заголовок поста"
              {...titleProps}
              classNames={{input: classes.postTitle}}
              onKeyDown={
                (e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                  }
                }
              }
              onChange={
                (e) => {
                  e.target.value = e.target.value.replace(/[\r\n]+/gm, ' ');
                  titleProps.onChange(e);
                }
              }
            />
          </Skeleton>
          <Skeleton visible={options?.loading}>
            <MultiSelect
              classNames={{
                root: classes.tagListRoot,
                input: classes.tagListInput,
                section: classes.tagListSection,
                pill: classes.tagListPill
              }}
              placeholder="Тэги"
              data={tags ?? []}
              value={tags?.length ? (form.values.tags as string[] ?? []) : []}
              onChange={(values: string[]) => {
                form.setFieldValue("tags", values as never);
              }}
            />
          </Skeleton>
          <Skeleton visible={options?.loading}>
            <BlocksField
              className={classes.contentInline}
              value={form.values.content as never}
              placeholder="Меня часто спрашивают..."
              onChange={(data) => {
                form.setFieldValue("content", data as never)
              }}
            />
          </Skeleton>
        </FieldGroup>
      </>
    }
  },
  {
    name: 'slug',
    label: 'Слаг',
    type: 'slug',
    required: true,
    placeholder: 'url-friendly-name',
    url: (slug: string) => `http://bc.local/blog/${slug}`,
    validate: (v) => /^[a-z0-9-]+$/.test(v as string)
      ? null
      : 'Только латиница, цифры и дефис',
  },
  {
    name: 'published',
    label: 'Опубликован',
    type: 'switch'
  },
  {
    name: 'publishDate',
    label: 'Дата публикации',
    type: 'datetime',
    required: true
  },
  {
    name: 'updateDate',
    label: 'Дата обновления',
    type: 'datetime',
    clearable: true
  },
  {
    name: 'shortTitle',
    label: 'Краткий заголовок',
    hint: 'Для списка постов и meta title',
    type: "text",
    span: "full"
  },
  {
    name: "annotation",
    label: "Аннотация",
    hint: "Для списка постов",
    type: "text",
    span: "full",
    softMaxLength: 125
  },
  {
    name: 'coverImage',
    label: 'Обложка',
    type: 'image',
    previewWidth: 400,
    thumbnailWidth: 100,
    uploadPurpose: 'cover',
    span: 'full',
  },
  {
    name: "metaDescription",
    label: "Meta description",
    type: "text",
    span: "full",
    hint: "Краткое описание для поисковых систем",
    softMaxLength: 160,
  }
];

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
    <EntityForm
      fields={FIELDS}
      dataProvider={dataProvider}
      initialValues={isCreateMode ? { published: false } : undefined}
      context={context}
      onSubmit={async (values: any) => {
        if (isCreateMode) {
          const result = await apiCall('POST', 'post', values);
          notify.success('Создано', `"${values.title}" создан`);
          return result;
        } else {
          await apiCall('PUT', `post/${id}`, values);
          notify.success('Сохранено', `Пост обновлён.`);
        }
      }}
      onCreated={(result: any) => {
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
