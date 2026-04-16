import {List} from "@/components/List/List.tsx";
import dataProvider from "./dataProvider";
import type {ColumnDef} from "@/components/DataTable";
import {Badge} from "@mantine/core";
import {PageRow} from "./types";

const columns: ColumnDef<PageRow>[] = [
  {
    key: 'title',
    header: 'Заголовок',
    sortable: true,
    link: (row) => `/admin/pages/${row.id}`,
  },
  {
    key: 'slug',
    header: 'Слаг',
    sortable: true,
    render: (row) => <code>/{row.slug}</code>,
  },
  {
    key: 'status',
    header: 'Статус',
    sortable: true,
    render: (row) => (
      <Badge
        color={row.status === 'published' ? 'teal' : 'gray'}
        variant="light"
      >
        {row.status}
      </Badge>
    ),
  },
  { key: 'publishDate', header: 'Дата публикации', sortable: true, width: 120 },
];

export default function BlogPosts() {
  return <List<PageRow>
    name="posts"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: false, filter: true}}
    dataProvider={dataProvider}
  />
}
