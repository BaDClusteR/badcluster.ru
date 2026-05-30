import {File, Media, Nullable} from "@admin/types";

export interface GameMaterial {
  title: string,
  shortTitle: string,
  gameId: number,
  dateAdded: Nullable<string>,
  annotation: string,
  description: Record<string, unknown>[]
  setupInstructions: Record<string, unknown>[]
  file: Nullable<File>,
  type: "F" | "A",
  slug: string,
  url: string
}

export interface MaterialGame {
  id: number,
  title: string,
  cover: Nullable<Media>,
  slug: string
}

export interface MaterialGamesCallResult {
  games: MaterialGame[];
}

export interface GameMaterialContext {
  games: MaterialGame[];
}
