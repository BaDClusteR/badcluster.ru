import { MultiSelect, Skeleton, Textarea } from "@mantine/core";
import type { FieldDef } from "@admin/types";
import classes from "./styles.module.css";
import { Post} from "./types";
import placeholders from "./placeholders";

export interface BlogPostContext {
  tags: { value: string; label: string }[];
}

const FIELDS: FieldDef<Post, BlogPostContext>[] = [
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
            <BlocksField
              className={classes.contentInline}
              value={form.values.content as never}
              placeholder={placeholders[Math.floor(Math.random() * placeholders.length)]}
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
    thumbnailHeight: 70,
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

export default FIELDS;
