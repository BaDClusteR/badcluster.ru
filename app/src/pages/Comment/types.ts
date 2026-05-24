import {GeoIp, Nullable, Optional} from "@admin/types";

export interface Comment {
  date: string,
  name: string,
  comment: string,
  status: string
}

export interface CommentContext {
  dateHumanReadable: string,
  geoIp: GeoIp,
  page: string,
  pageLink: string,
  email: Optional<string>,
  parent: Nullable<CommentParent>,
  name: string
}

export interface CommentParent {
  id: number,
  title: string,
  text: string,
  link: string
}
