export interface PostDetailed {
    id: number,
    title: string,
    createdDate: string,
    publishDate: string,
    updateDate: string,
    content: Record<string, unknown>[],
    published: boolean,
    slug: string
}

export interface TagApi {
    id: number,
    title: string
}

export interface TagsApiCallResult {
    tags: TagApi[]
}
