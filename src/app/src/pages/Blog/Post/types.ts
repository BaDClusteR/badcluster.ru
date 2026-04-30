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
