import type {ReactNode} from "react";
import {Nullable} from "@/types";
import {SortDirection} from "../List/types";

export interface TableSort {
    sortBy: Nullable<string>,
    sortDir: SortDirection
}

export interface TableState {
    page: number,
    perPage: number,
    sortBy: Nullable<string>;
    sortDir: SortDirection,
}

/**
 * Abstraction over where the table state lives.
 * Swap the backing implementation (URL / localStorage / memory / server)
 * without touching the DataTable component itself.
 */
export interface TableStateManager {
    state: TableState;

    setState(patch: Partial<TableState>): void;
}

export interface ColumnDef<T> {
    /** Unique column key. Used as React key and matches state.sortBy when sortable. */
    key: string,
    subKey?: string,
    header: ReactNode,
    sortable?: boolean,
    width?: number | string,
    align?: "left" | "right" | "center",
    /** Custom cell renderer — takes precedence over accessor. */
    render?: (row: T) => ReactNode,
    subRender?: (row: T) => ReactNode,
    /** Simple value extractor used when `render` is not provided. */
    accessor?: (row: T) => ReactNode,
    /** If set, cell is rendered as an SPA link to the returned path. */
    link?: (row: T) => string,
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
    bulkActions?: ReactNode
}
