import {ColumnDef, TableState} from "@/components/DataTable";
import {ReactNode} from "react";

export interface ListState {
    table: TableState,
    filter: string
}

export interface PartialListState {
    table?: Partial<TableState>,
    filter?: string
}

export interface ListProps<T> {
    name: string,
    title: ReactNode,
    searchPlaceHolder?: string,
    permissions: ListPermissions,
    defaults?: PartialListState,
    dataProvider: ListDataProvider<any>,
    columns: ColumnDef<any>[],
    getEditLink?: (row: T) => string,
    getDeleteLink?: (row: T) => string,
}

export type ListDataProviderRequest<T> = (state: ListState, options: ListDataProviderRequestOptions) => Promise<ListDataResponse<T>>;

export interface ListDataProvider<T> {
    getData: ListDataProviderRequest<T>,
}

export interface ListDataProviderRequestOptions {
    signal?: AbortSignal
}

export interface ListPermissions {
    add: boolean,
    edit: boolean,
    delete: boolean,
    select: boolean,
    filter: boolean
}

/**
 * Abstraction over where the table state lives.
 * Swap the backing implementation (URL / localStorage / memory / server)
 * without touching the DataTable component itself.
 */
export interface ListStateManager {
    state: ListState;
    setState(patch: PartialListState): void;
}

export interface ListDataResponse<T> {
    rows: T[],
    total: number
}

export interface ListRequestParameters {
    filter?: string,
    sortBy?: string,
    sortDir?: SortDirection,
    page?: number,
    perPage?: number
}

export type SortDirection = 'asc' | 'desc';

export interface EntityRow {
    id: number
}
