import {Media, Nullable} from "@admin/types";

export interface Game {
  title: string,
  slug: string,
  cover: Nullable<Media>
}
