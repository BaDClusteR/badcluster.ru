import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import {Fact} from "./types";

interface FactApiCallResult {
  content: string;
}

const getDataProvider = (id: string | undefined): EntityFormDataProvider<Fact> | undefined => {
  if (!id) {
    return undefined;
  }

  return {
    queryKey: ["fact", id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      const raw = await apiCall("GET", `fact/${id}`, {}, {signal}) as FactApiCallResult;

      return {
        content: raw.content
      };
    }
  };
};

export default getDataProvider;
