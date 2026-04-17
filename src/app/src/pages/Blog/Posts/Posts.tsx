import {List} from "@/components/List/List.tsx";
import dataProvider from "./dataProvider";
import type {ColumnDef} from "@/components/DataTable";
import {PageRow} from "./types";
import {BadgeGray, BadgeGreen} from "@/components/Badge/Badge";

const columns: ColumnDef<PageRow>[] = [
  {
    key: 'title',
    header: 'Заголовок',
    sortable: true,
    link: (row) => `/admin/posts/${row.id}`,
  },
  {
    key: 'slug',
    header: 'Слаг',
    sortable: true,
    render: (row) => <code>{row.slug}</code>,
  },
  {
    key: 'publish',
    header: 'Статус',
    sortable: true,
    render: (row) => (
      row.published
        ? <BadgeGreen>Опубликован</BadgeGreen>
        : <BadgeGray>Драфт</BadgeGray>
    ),
  },
  { key: 'publishDate', header: 'Дата публикации', sortable: true, width: 120 },
];

export default function BlogPosts() {
  return <List<PageRow>
    name="posts"
    title="Посты"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: false, filter: true}}
    dataProvider={dataProvider}
    getEditLink={(row: PageRow) => `/admin/posts/${row.id}`}
    getDeleteLink={(row: PageRow) => `/admin/posts/delete/${row.id}`}
  />
}
