import type { DataTableProps } from "./types";
import { EntityRow } from "@/components/List/types.ts";
export declare function DataTable<T extends EntityRow>({ columns, rows, total, state, actions, loading, perPageOptions, emptyMessage, onStateChange, error, errorContent, selectable, selectedRows, onSelectionChange, bulkActions }: DataTableProps<T>): import("react/jsx-runtime").JSX.Element;
