import { useNavigate, useParams } from "react-router";
import { MultiSelect, Skeleton, Textarea } from "@mantine/core";
import { useQuery } from '@tanstack/react-query';
import { useAdminCore } from '../admin/useAdminCore';
import type { EntityFormDataProvider, FieldDef } from '../admin/types';
import classes from "./styles.module.css";
import { PostDetailed, TagApi, TagsApiCallResult } from "./types";

interface BlogPostContext {
  tags: { value: string; label: string }[];
}

const FIELDS: FieldDef<PostDetailed, BlogPostContext>[] = [
  {
    type: 'group',
    role: 'primary',
    render: (form, options) => {
      const tags = options?.context?.tags;
      const { BlocksField, FieldGroup } = options!.components;
      const titleProps = form.getInputProps('title');
      return <>
        <FieldGroup>
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
              data={tags}
              loading={Array.isArray(tags)}
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
    span: 'half',
  },
  {
    name: 'published',
    label: 'Опубликован',
    type: 'switch',
    span: 'half',
  },
  {
    name: 'publishDate',
    label: 'Дата публикации',
    type: 'datetime',
    required: true,
    span: 'half'
  },
  {
    name: 'updateDate',
    label: 'Дата обновления',
    type: 'datetime',
    span: 'half',
    clearable: true
  }
];

export function BlogPost() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { EntityForm, apiCall, notify } = useAdminCore();

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

  const dataProvider: EntityFormDataProvider<PostDetailed> = {
    queryKey: ['post', id],
    getData: async (signal) => {
      return await apiCall('GET', `post/${id}`, {}, { signal }) as PostDetailed;
    }
  };

  return (
    <EntityForm
      fields={FIELDS}
      dataProvider={dataProvider}
      context={context}
      onSubmit={async (values: any) => {
        console.log('Submitting', values);
        await new Promise((r) => setTimeout(r, 500));
        notify.success('Saved', `"${values.title}" updated`);
      }}
      onCancel={() => navigate('/admin/blog')}
      notFoundText="Пост не найден"
      notFoundBtnCaption="Назад к постам"
    />
  );
}
