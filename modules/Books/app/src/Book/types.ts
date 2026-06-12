import {Media, Nullable, StringKeyObject} from "@admin/types";

export interface Book {
  slug: string,
  cover: Nullable<Media>,
  coverBg: Nullable<Media>
  title: string,
  author: string,
  annotation: string,
  shortAnnotation: string,
  type: "A" | "T",
  lastUpdateDate: string,
  group: string,
  position: number,
  fb2Genre: string,
  formats: {
    [key: string]: BookFormat
  }
}

export interface BookContext {
  formats: string[];
}

export interface BookFormat {
  id: number,
  type: string,
  allowed: boolean,
  filename: string,
  size: number,
  sizeHumanReadable: string,
  dateGenerated: string,
  postfix: string
}
