import type {EntityRow} from "@admin/types";

export interface RedirectRow extends EntityRow {
  path: string,
  code: number,
  destination: string
}