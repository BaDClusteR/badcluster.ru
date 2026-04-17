import {EntityRow} from "@/components/List/types.ts";

export interface PageRow extends EntityRow {
    title: string,
    slug: string,
    published: boolean,
    publishDate: string,
}
