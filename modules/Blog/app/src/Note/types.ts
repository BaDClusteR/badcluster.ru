export interface Note {
  id: number,
  title: string,
  publishDate: string,
  content: Record<string, unknown>[],
  published: boolean,
  slug: string,
  metaDescription: string
}
