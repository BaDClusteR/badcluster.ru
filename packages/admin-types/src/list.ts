import type {ReactNode} from "react";
import type {ColumnDef} from "./data-table";
import {Nullable} from "./common";

export type SortDirection = "asc" | "desc";

export interface EntityRow {
  id: number;
}

export interface ListState {
  table: {
    page: number,
    perPage: number,
    sortBy: string | null,
    sortDir: SortDirection,
  },
  filter: string
}

export interface PartialListState {
  table?: Partial<TableState>,
  filter?: string
}

export interface ListDataResponse<T> {
  items: T[],
  total: number
}

export interface ListStateManager {
  state: ListState,

  setState(patch: PartialListState): void
}

export interface ListRequestParameters {
  filter?: string,
  sortBy?: string,
  sortDir?: SortDirection,
  page?: number,
  perPage?: number
}

export interface ListDataProviderRequestOptions {
  signal?: AbortSignal;
}

export type ListDataProviderRequest<T> = (
  state: ListState,
  options: ListDataProviderRequestOptions
) => Promise<ListDataResponse<T>>;

export interface ListDataProvider<T> {
  getData: ListDataProviderRequest<T>;
}

export interface ListPermissions {
  add: boolean,
  edit: boolean,
  delete: boolean,
  select: boolean,
  filter: boolean
}

export interface ListProps<T extends EntityRow> {
  name: string,
  permissions?: ListPermissions,
  columns: ColumnDef<T>[],
  labels: ListLabels<T>,
  webPath?: string,
  dataProvider?: ListDataProvider<T>,
  apiEndpoint?: string,
}

export interface ListLabels<T extends EntityRow> {
  title: ReactNode,
  searchPlaceholder?: string,
  deleteConfirmation?: {
    multiple: string,
    single: (row: T) => ReactNode
  },
  add?: string
}

export interface TableState {
  page: number,
  perPage: number,
  sortBy: Nullable<string>,
  sortDir: SortDirection,
}

export interface DataTableProps<T> {
  columns: ColumnDef<T>[],
  rows: T[],
  /** Total number of rows across all pages (for pagination). */
  total: number,
  state: TableState,
  /** Action buttons rendered in the last column. */
  actions?: (row: T) => ReactNode,
  loading?: boolean,
  /** Options for the "per page" selector. */
  perPageOptions?: number[],
  emptyMessage?: ReactNode,
  onStateChange: (state: TableState) => void,
  error?: boolean,
  errorContent?: ReactNode,
  selectable?: boolean,
  selectedRows?: boolean[],
  onSelectionChange?: (selectedRows: boolean[]) => void,
  bulkActions?: ReactNode,
  webPath?: string
}

export interface TableSort {
  sortBy: Nullable<string>,
  sortDir: SortDirection
}
