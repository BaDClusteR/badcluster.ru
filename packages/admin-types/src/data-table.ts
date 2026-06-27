import type {ReactNode} from "react";

export interface ColumnDef<T> {
  key: keyof T,
  subKey?: keyof T,
  header: ReactNode,
  sortable?: boolean,
  width?: number | string,
  align?: "left" | "right" | "center",
  render?: (row: T) => ReactNode,
  subRender?: (row: T) => ReactNode,
  accessor?: (row: T) => ReactNode,
  nowrap?: boolean,
  link?: boolean | ((row: T) => string);
}
