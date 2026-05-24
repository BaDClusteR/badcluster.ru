import type {EntityFormDataProvider} from "@admin/types";
import apiCall from "@/utils/apiCall";

export function createEntityFormDataProvider<T>(
  apiEndpoint: string,
  id: string | undefined,
  isCreateMode: boolean
): EntityFormDataProvider<T> | undefined {
  if (isCreateMode || !id) return undefined;

  return {
    queryKey: [apiEndpoint, id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      return await apiCall("GET", `${apiEndpoint}/${id}`, {}, {signal}) as T;
    }
  };
}
