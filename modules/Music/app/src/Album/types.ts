import {Media, Nullable} from "@admin/types";

export interface Album {
  title: string,
  cover: Nullable<Media>,
  slug: string,
  genre: string,
  type: "S" | "E" | "A",
  releaseDate: string,
  annotation: string,
  shortAnnotation: string,
  musicBy: string,
  visualBy: string,
  coverBy: string,
  position: number
}
