import {List} from "@/components/List/List.tsx";
import dataProvider from "./dataProvider";
import type {ColumnDef} from "@/components/DataTable";
import {PageRow} from "./types";
import {BadgeGray, BadgeGreen} from "@/components/primitives/Badge";
import {notify} from "@/lib/notify.ts";
import apiCall from "@/utils/apiCall.ts";

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
  {
    key: 'publishDate',
    subKey: 'publishTime',
    header: 'Дата публикации',
    sortable: true,
    width: 120
  },
];

export default function BlogPosts() {
  return <List<PageRow>
    name="posts"
    title="Посты"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    dataProvider={dataProvider}
    getEditLink={(row: PageRow) => `/admin/posts/${row.id}`}
    onAdd={() => notify.info('Пока не сделано', 'Скоро доделаем добавление :-[')}
    addButtonTitle="Новый пост"
    getDeleteConfirmationText={(rows: PageRow|PageRow[]) => {
      if (Array.isArray(rows) && rows.length === 1) {
        rows = rows[0];
      }
      return Array.isArray(rows)
        ? `Действительно удалить выбранные посты (${rows.length})?`
        : <>Действительно удалить <strong>{rows.title}</strong>?</>;
    }}
    getDeleteConfirmationTitle={(_row: PageRow|PageRow[]) => "Удаление постов"}
    onDelete={async (rows: PageRow[]) => {
      await apiCall(
        "POST",
        "posts/delete",
        {
          rows: rows.map(
            (row) => row.id
          )
        }
      );
    }}
  />
}
