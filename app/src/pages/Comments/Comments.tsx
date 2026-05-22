import type {
  ColumnDef,
  ListDataProvider,
  ListDataProviderRequestOptions,
  ListDataResponse,
  ListState
} from "@admin/types";
import { CommentRow } from "./types";
import apiCall from "@/utils/apiCall";
import convertListStateToQueryParameters from "@/components/List/utils/convertListStateToQueryParameters";
import {List} from "@/components/List/List";
import classes from "./Comments.module.css";
import {Link} from "react-router";
import {BadgeGray, BadgeGreen, BadgeRed, BadgeYellow} from "@/components/primitives/Badge";

export const ENDPOINT = "comments";
export const API_ENDPOINT = "comments";
export const ROOT_ENDPOINT = `/admin/${ENDPOINT}`;

const getEditLink = (row: CommentRow) => `${ROOT_ENDPOINT}/${row.id}`;

const columns: ColumnDef<CommentRow>[] = [
  {
    key: 'date',
    header: 'Дата',
    sortable: true,
    link: getEditLink,
    render: row => <span style={{whiteSpace: 'nowrap'}}>{row.date}</span>
  },
  {
    key: 'name',
    header: 'Никнейм',
    sortable: true
  },
  {
    key: 'comment',
    header: 'Комментарий',
    render: row => <span className={classes.comment} dangerouslySetInnerHTML={{__html: row.comment}} />
  },
  {
    key: 'page',
    header: 'Страница',
    render: row => row.pageLink
      ? <Link to={row.pageLink}>{row.page}</Link>
      : row.page
  },
  {
    key: 'status',
    header: 'Статус',
    render: row => {
      if (row.status === 'A') {
        return <BadgeGreen className={classes.approvedBadge}>Подтвержден</BadgeGreen>
      }

      if (row.status === 'M') {
        return <BadgeGray>На модерации</BadgeGray>
      }

      if (row.status === 'D') {
        return <BadgeRed>Отклонен</BadgeRed>
      }

      return <BadgeYellow>Неизвестный статус: {row.status}</BadgeYellow>
    }
  }
];

export default function Comments() {
  const dataProvider: ListDataProvider<CommentRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      const rows = await apiCall(
        'GET',
        API_ENDPOINT,
        convertListStateToQueryParameters(state),
        { signal: options.signal }
      ) as ListDataResponse<CommentRow>;

      return {
        items: rows.items,
        total: rows.total
      };
    }
  };

  return <List
    name="comments"
    title="Комментарии"
    columns={columns}
    permissions={{add: false, edit: true, delete: true, select: true, filter: true}}
    dataProvider={dataProvider}
    getEditLink={getEditLink}
    getDeleteConfirmationText={(rows: CommentRow|CommentRow[]) => {
      if (Array.isArray(rows) && rows.length === 1) {
        rows = rows[0];
      }
      return Array.isArray(rows)
        ? `Действительно удалить выбранные комментарии (${rows.length})?`
        : <>Действительно удалить от <strong>{rows.name}</strong>?</>;
    }}
    getDeleteConfirmationTitle={(_row: CommentRow|CommentRow[]) => "Удаление комментов"}
    onDelete={async (rows: CommentRow[]) => {
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
