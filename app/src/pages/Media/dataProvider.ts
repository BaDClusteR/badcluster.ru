import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import {MediaApiCallResult, MediaContext, MediaFile} from "./types";

const getDataProvider = (
  id: number,
  setContext: (value: React.SetStateAction<MediaContext | undefined>) => void
): EntityFormDataProvider<MediaFile> => (
  {
    queryKey: ["media_file", id],
    entityId: id,
    getData: async (signal) => {
      const raw = await apiCall("GET", `media_file/${id}`, {}, {signal}) as MediaApiCallResult;
      setContext({
        url: raw.url,
        filename: raw.filename,
        mime: raw.mime,
        thumbs: raw.thumbs
      });

      return {
        width: raw.width,
        height: raw.height,
        alt: raw.alt
      };
    }
  }
);

export default getDataProvider;