import type { ReactNode } from "react";
import type { ColumnDef } from "./data-table";

export type SortDirection = 'asc' | 'desc';

export interface EntityRow {
  id: number;
}

export interface ListState {
  table: {
    page: number;
    perPage: number;
    sortBy: string | null;
    sortDir: SortDirection;
  };
  filter: string;
}

export interface ListDataResponse<T> {
  rows: T[];
  total: number;
}

export interface ListDataProviderRequestOptions {
  signal?: AbortSignal;
}

export type ListDataProviderRequest<T> = (
  state: ListState,
  options: ListDataProviderRequestOptions,
) => Promise<ListDataResponse<T>>;

export interface ListDataProvider<T> {
  getData: ListDataProviderRequest<T>;
}

export interface ListPermissions {
  add: boolean;
  edit: boolean;
  delete: boolean;
  select: boolean;
  filter: boolean;
}

export interface ListProps<T extends EntityRow> {
  name: string;
  title: ReactNode;
  searchPlaceHolder?: string;
  permissions: ListPermissions;
  dataProvider: ListDataProvider<any>;
  columns: ColumnDef<any>[];
  getEditLink?: (row: T) => string;
  getDeleteConfirmationTitle?: (row: T | T[]) => ReactNode | null;
  getDeleteConfirmationText?: (row: T | T[]) => ReactNode | null;
  onAdd?: () => void;
  onDelete?: (rows: T[]) => Promise<void>;
  addButtonTitle?: string;
}