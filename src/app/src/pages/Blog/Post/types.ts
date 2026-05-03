import {StringKeyObject} from "@/types.ts";

export interface PostDetailed {
    id: number,
    title: string,
    createdDate: string,
    publishDate: string,
    updateDate: string,
    content: StringKeyObject[],
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
