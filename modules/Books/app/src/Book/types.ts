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
  technicalInfo: string | StringKeyObject, // StringKeyObject or JSON-encoded StringKeyObject
  group: string,
  position: number,
  formats: {
    [key: string]: BookFormat
  }
}

export interface BookContext {
  formats: string[];
}

export interface BookFormat {
  type: string,
  allowed: boolean,
  filename: string,
  size: number,
  sizeHumanReadable: string,
  dateGenerated: string
}
