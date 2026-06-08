import type {EntityRow} from "@admin/types";

export interface GameMaterialRow extends EntityRow {
  game: {
    id: number,
    title: string,
  },
  title: string,
  game_title: string,
  date_added: string,
  type: string,
  annotation: string
}
