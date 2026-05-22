import type { EntityRow } from "@admin/types";

export interface PostRow extends EntityRow {
    title: string,
    slug: string,
    published: boolean,
    publishDate: string,
    updateDate: string
}
