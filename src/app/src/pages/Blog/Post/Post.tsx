import {useNavigate, useParams} from "react-router";
import {MultiSelect, Skeleton, Textarea} from "@mantine/core";
import { EntityForm, type EntityFormDataProvider, type FieldDef } from '@/components/EntityForm';
import { notify } from '@/lib/notify';
import apiCall from '@/utils/apiCall';
import {BlocksField} from "@/components/EntityForm/fields/BlocksField.tsx";
import FieldGroup from "@/components/EntityForm/FieldGroup.tsx";
import classes from "./styles.module.css";
import {PostDetailed} from "@/pages/Blog/Post/types.ts";
import {Optional} from "@/types.ts";
import {EntityFormRenderOptions} from "@/components/EntityForm/types.ts";

const FIELDS: FieldDef<PostDetailed>[] = [
  {
    type: 'group',
    role: 'primary',
    render: (form, options?: EntityFormRenderOptions) => {
      return <>
        <FieldGroup>
          <Skeleton visible={options?.loading}>
            <Textarea
              autosize
              placeholder="Заголовок поста"
              {...form.getInputProps('title')}
              classNames={{input: classes.postTitle}}
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
              data={['React', 'Angular', 'Vue', 'Svelte']}
            />
          </Skeleton>
          <Skeleton visible={options?.loading}>
            <BlocksField
              className={classes.contentInline}
              value={form.values.content as never}
              placeholder="Меня часто спраiивают..."
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
    type: 'text',
    required: true,
    placeholder: 'url-friendly-name',
    span: 'half'
  },
  {
    name: 'published',
    label: 'Опубликован',
    type: 'switch',
    description: 'Show on the home page',
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
    required: true,
    span: 'half'
  }
];

export function BlogPost() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const dataProvider: EntityFormDataProvider<PostDetailed> = {
    queryKey: ['page', id],
    getData: async (signal) => {
      return await apiCall('GET', `post/${id}`, {}, { signal }) as PostDetailed;
    },
    getTitle: (data: Optional<PostDetailed>): string => data?.title ?? 'Заголовок поста'
  };

  return (
    <>
      <EntityForm
        fields={FIELDS}
        dataProvider={dataProvider}
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
