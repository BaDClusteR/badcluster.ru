export interface PostDetailed {
  id: number,
  title: string,
  shortTitle: string,
  annotation: string,
  publishDate: string,
  updateDate: string,
  content: Record<string, unknown>[],
  published: boolean,
  slug: string,
  metaDescription: string,
  coverImage?: Media | null,
  tags: string[]
}

export interface Media {
  id: number,
  url: string,
  width: number,
  height: number,
  mime: string,
  alt: string,
  thumbs?: MediaThumbnail[]
}

export interface MediaThumbnail {
  width: number,
  height: number,
  url: string,
  mime: string
}

export interface TagApi {
  id: number,
  title: string
}

export interface TagsApiCallResult {
  tags: TagApi[]
}
