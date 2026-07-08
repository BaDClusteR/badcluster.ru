import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import {PhotoTag} from "./types";

interface PhotoTagApiCallResult {
  title: string;
  position: number;
}

const getDataProvider = (id: string | undefined): EntityFormDataProvider<PhotoTag> | undefined => {
  if (!id) {
    return undefined;
  }

  return {
    queryKey: ["photo_tag", id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      const raw = await apiCall("GET", `photo_tag/${id}`, {}, {signal}) as PhotoTagApiCallResult;

      return {
        title: raw.title,
        position: raw.position
      };
    }
  };
};

export default getDataProvider;
