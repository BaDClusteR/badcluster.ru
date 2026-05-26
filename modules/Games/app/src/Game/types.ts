import {Media, Nullable} from "@admin/types";

export interface Game {
  title: string,
  releaseYear: Nullable<number>,
  cover: Nullable<Media>
}
