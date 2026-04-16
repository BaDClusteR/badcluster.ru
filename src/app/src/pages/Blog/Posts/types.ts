import {EntityRow} from "@/components/List/types.ts";

export interface PageRow extends EntityRow {
    title: string,
    slug: string,
    status: 'draft' | 'published',
    publishDate: string,
}
