import type {OutputData} from "@editorjs/editorjs";

export interface StaticPage {
  title: string;
  shortTitle: string;
  content: OutputData | null;
  slug: string;
  published: boolean;
  indexable: boolean;
  textBlock: boolean;
  publishDate: string;
  metaDescription: string;
  backLinkText: string;
  backLinkUrl: string;
}
