import type {FieldDef} from "@admin/types";
import {BlogPostContext, Post} from "../types";
import PostEditor from "./PostEditor";

const FIELDS: FieldDef<Post, BlogPostContext>[] = [
  {
    type: "group",
    role: "primary",
    span: "full",
    render: (form, options) =>
      <PostEditor form={form} options={options}/>
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    required: true,
    placeholder: "url-friendly-name",
    url: (slug: string) => `http://bc.local/blog/${slug}`
  },
  {
    name: "published",
    label: "Опубликован",
    type: "switch"
  },
  {
    name: "publishDate",
    label: "Дата публикации",
    type: "datetime",
    required: true
  },
  {
    name: "updateDate",
    label: "Дата обновления",
    type: "datetime",
    clearable: true
  },
  {
    name: "shortTitle",
    label: "Краткий заголовок",
    hint: "Для списка постов и meta title",
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
    name: "coverImage",
    label: "Обложка",
    type: "image",
    previewWidth: 400,
    thumbnailWidth: 100,
    thumbnailHeight: 70,
    uploadPurpose: "cover",
    span: "full"
  },
  {
    name: "metaDescription",
    label: "Meta description",
    type: "text",
    span: "full",
    hint: "Краткое описание для поисковых систем",
    softMaxLength: 160
  }
];

export default FIELDS;
