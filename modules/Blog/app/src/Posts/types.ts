import type { EntityRow } from "../admin/types";

export interface PageRow extends EntityRow {
    title: string,
    slug: string,
    published: boolean,
    publishDate: string,
    publishTime: string
}