import {Media} from "@admin/types";

export interface BlogPostContext {
  tags: { value: string; label: string }[];
}

export interface Post {
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

export interface TagApi {
  id: number,
  title: string
}

export interface TagsApiCallResult {
  tags: TagApi[]
}
