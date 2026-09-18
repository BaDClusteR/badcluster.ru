import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import {StaticPage} from "./types";

interface StaticPageApiCallResult {
  title: string;
  shortTitle: string;
  content: StaticPage["content"];
  slug: string;
  published: boolean;
  indexable: boolean;
  textBlock: boolean;
  publishDate: string;
  metaDescription: string;
  backLinkText: string;
  backLinkUrl: string;
}

const getDataProvider = (id: string | undefined): EntityFormDataProvider<StaticPage> | undefined => {
  if (!id) {
    return undefined;
  }

  return {
    queryKey: ["static-page", id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      const raw = await apiCall("GET", `static-page/${id}`, {}, {signal}) as StaticPageApiCallResult;

      return {
        title: raw.title,
        shortTitle: raw.shortTitle,
        content: raw.content,
        slug: raw.slug,
        published: raw.published,
        indexable: raw.indexable,
        textBlock: raw.textBlock,
        publishDate: raw.publishDate,
        metaDescription: raw.metaDescription,
        backLinkText: raw.backLinkText,
        backLinkUrl: raw.backLinkUrl
      };
    }
  };
};

export default getDataProvider;
