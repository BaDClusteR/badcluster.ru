import { useAdminCore } from "../admin/useAdminCore";
import type { ColumnDef, ListDataProvider, ListDataProviderRequestOptions, ListState } from "@admin/types";
import { PageRow } from "./types";
import {useNavigate} from "react-router";

const columns: ColumnDef<PageRow>[] = [
  {
    key: 'title',
    header: 'Заголовок',
    sortable: true,
    link: (row) => `/admin/blog/${row.id}`,
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
  const { List, BadgeGray, BadgeGreen, apiCall, notify, convertListStateToQueryParameters } = useAdminCore();

  // Inject badge renders now that we have the components
  const columnsWithBadges = columns.map(col => {
    if (col.key === 'publish') {
      return {
        ...col,
        render: (row: PageRow) => (
          row.published
            ? <BadgeGreen>Опубликован</BadgeGreen>
            : <BadgeGray>Драфт</BadgeGray>
        ),
      };
    }
    return col;
  });

  const navigate = useNavigate();

  const dataProvider: ListDataProvider<PageRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      const rows = await apiCall(
        'GET',
        'posts',
        convertListStateToQueryParameters(state),
        { signal: options.signal }
      ) as { posts: PageRow[] };

      return {
        rows: rows.posts,
        total: rows.posts.length
      };
    }
  };

  return <List<PageRow>
    name="posts"
    title="Посты"
    columns={columnsWithBadges}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    dataProvider={dataProvider}
    getEditLink={(row: PageRow) => `/admin/blog/${row.id}`}
    onAdd={() => navigate("/admin/blog/new")}
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
        "DELETE",
        "blog",
        {
          rows: rows.map(row => row.id)
        }
      );
    }}
  />
}
