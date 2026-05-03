import {useNavigate, useParams} from "react-router";
import {MultiSelect, Skeleton, Textarea} from "@mantine/core";
import { useQuery } from '@tanstack/react-query';
import { EntityForm, type EntityFormDataProvider, type FieldDef } from '@/components/EntityForm';
import { notify } from '@/lib/notify';
import apiCall from '@/utils/apiCall';
import {BlocksField} from "@/components/EntityForm/fields/BlocksField.tsx";
import FieldGroup from "@/components/EntityForm/FieldGroup.tsx";
import classes from "./styles.module.css";
import {PostDetailed, TagApi, TagsApiCallResult} from "@/pages/Blog/Post/types.ts";
import {Optional} from "@/types.ts";

interface BlogPostContext {
  tags: { value: string; label: string }[];
}

const FIELDS: FieldDef<PostDetailed, BlogPostContext>[] = [
  {
    type: 'group',
    role: 'primary',
    render: (form, options) => {
      const tags = options?.context?.tags;
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
    span: 'half',
    url: (slug: string) => `http://bc.local/blog/${slug}`
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

  const { data } = useQuery({
    queryKey: ['tags'],
    queryFn: ({ signal }) => apiCall('GET', 'tags', {}, { signal }),
  });

  const tagsRaw: Optional<TagsApiCallResult> = data as any;

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
    <>
      <EntityForm
        fields={FIELDS}
        dataProvider={dataProvider}
        context={context}
        onSubmit={async (values) => {
          // eslint-disable-next-line no-console
          console.log('Submitting', values);
          await new Promise((r) => setTimeout(r, 500));
          notify.success('Saved', `"${values.title as string}" updated`);
        }}
        onCancel={() => navigate('/admin/posts')}
        notFoundText="Пост не найден"
        notFoundBtnCaption="Назад к постам"
      />
    </>
  );
}
