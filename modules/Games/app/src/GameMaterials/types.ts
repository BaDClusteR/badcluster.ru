import type {EntityRow} from "@admin/types";

export interface GameMaterialRow extends EntityRow {
  game: {
    id: number,
    title: string,
  },
  title: string,
  date: string,
  type: string,
  annotation: string
}
