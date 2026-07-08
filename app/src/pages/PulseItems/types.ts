import type {EntityRow} from "@admin/types";

export interface PulseItemRow extends EntityRow {
  image: string | null,
  title: string,
  text: string,
  position: number
}
