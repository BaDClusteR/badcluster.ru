import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import {Redirect} from "./types";

interface RedirectApiCallResult {
  path: string;
  code: number;
  destination: string;
}

const getDataProvider = (id: string | undefined): EntityFormDataProvider<Redirect> | undefined => {
  if (!id) {
    return undefined;
  }

  return {
    queryKey: ["redirect", id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      const raw = await apiCall("GET", `redirect/${id}`, {}, {signal}) as RedirectApiCallResult;

      return {
        path: raw.path,
        // API отдает код числом, селект работает со строкой
        code: String(raw.code),
        destination: raw.destination
      };
    }
  };
};

export default getDataProvider;