import { useAdminCore } from "../admin/useAdminCore";
import type {
  ColumnDef,
  ListDataProvider,
  ListDataProviderRequestOptions,
  ListDataResponse,
  ListState
} from "@admin/types";
import { TagRow } from "./types";
import {useNavigate} from "react-router";

export const ENDPOINT = "blog/tags";
export const API_ENDPOINT = "post_tags";
export const API_ENDPOINT_SINGLE_ENTITY = "post_tag";
export const ROOT_ENDPOINT = `/admin/${ENDPOINT}`;

const getEditLink = (row: TagRow) => `${ROOT_ENDPOINT}/${row.id}`;

const columns: ColumnDef<TagRow>[] = [
  {
    key: 'title',
    header: 'Название',
    sortable: true,
    link: getEditLink,
  },
  {
    key: 'slug',
    header: 'Слаг',
    sortable: true,
    render: (row) => <code>{row.slug}</code>,
  },
  {
    key: 'count',
    header: 'Кол-во постов',
    sortable: true
  }
];

export default function Tags() {
  const { List, apiCall, convertListStateToQueryParameters } = useAdminCore();

  const navigate = useNavigate();

  const dataProvider: ListDataProvider<TagRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      const rows = await apiCall(
        'GET',
        API_ENDPOINT,
        convertListStateToQueryParameters(state),
        { signal: options.signal }
      ) as ListDataResponse<TagRow>;

      return {
        items: rows.items,
        total: rows.total
      };
    }
  };

  return <List
    name="tags"
    title="Тэги"
    columns={columns}
    permissions={{add: true, edit: true, delete: true, select: true, filter: true}}
    dataProvider={dataProvider}
    getEditLink={getEditLink}
    onAdd={() => navigate(`${ROOT_ENDPOINT}/new`)}
    addButtonTitle="Новый тэг"
    getDeleteConfirmationText={(rows: TagRow|TagRow[]) => {
      if (Array.isArray(rows) && rows.length === 1) {
        rows = rows[0];
      }
      return Array.isArray(rows)
        ? `Действительно удалить выбранные тэги (${rows.length})?`
        : <>Действительно удалить <strong>{rows.title}</strong>?</>;
    }}
    getDeleteConfirmationTitle={(_row: TagRow|TagRow[]) => "Удаление тэгов"}
    onDelete={async (rows: TagRow[]) => {
      await apiCall(
        "DELETE",
        API_ENDPOINT,
        {
          rows: rows.map(row => row.id)
        }
      );
    }}
  />
}
