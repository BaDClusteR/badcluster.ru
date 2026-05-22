import {GeoIp, Nullable, Optional} from "@admin/types";

export interface CommentDetailed {
  date: string,
  name: string,
  comment: string,
  status: string
}

export interface CommentDetailedContext {
  dateHumanReadable: string,
  geoIp: GeoIp,
  page: string,
  pageLink: string,
  email: Optional<string>,
  parent: Nullable<CommentDetailedParent>,
  name: string
}

export interface CommentDetailedParent {
  id: number,
  title: string,
  text: string,
  link: string
}
