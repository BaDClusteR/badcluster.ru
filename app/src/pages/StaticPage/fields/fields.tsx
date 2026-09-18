import type {FieldDef} from "@admin/types";
import {StaticPage} from "../types";
import PageEditor from "./PageEditor";

const FIELDS: FieldDef<StaticPage>[] = [
  {
    type: "group",
    role: "primary",
    span: "full",
    render: (form, options) => <PageEditor form={form} options={options}/>
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    required: true,
    placeholder: "url-friendly-name",
    // Static pages sit at the site root — no prefix.
    url: (slug: string) => `/${slug}`
  },
  {
    name: "published",
    label: "Опубликована",
    type: "switch"
  },
  {
    name: "indexable",
    label: "Индексируется",
    type: "switch"
  },
  {
    name: "textBlock",
    label: "Сетка контента",
    type: "switch"
  },
  {
    name: "publishDate",
    label: "Дата публикации",
    type: "datetime",
    clearable: true,
    span: "full",
    hint: "Необязательно. На видимость страницы не влияет — только на микроразметку"
  },
  {
    name: "shortTitle",
    label: "Краткий заголовок",
    hint: "Для meta title, если основной заголовок слишком длинный",
    type: "text",
    span: "full"
  },
  {
    name: "metaDescription",
    label: "Meta description",
    type: "text",
    span: "full",
    hint: "Краткое описание для поисковых систем",
    softMaxLength: 160
  },
  {
    type: "heading",
    label: "Ссылка «назад»",
    span: "full",
    hint: "Ссылка появится, только если заполнены оба поля"
  },
  {
    name: "backLinkText",
    label: "Текст ссылки",
    type: "text",
    placeholder: "Назад к списку"
  },
  {
    name: "backLinkUrl",
    label: "Куда ведёт",
    type: "text",
    placeholder: "/blog"
  }
];

export default FIELDS;
