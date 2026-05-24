import type {ListDataProvider, ListDataProviderRequestOptions, ListDataResponse, ListState} from "@admin/types";
import apiCall from "@/utils/apiCall";
import convertListStateToQueryParameters from "@/components/List/utils/convertListStateToQueryParameters";

export default function getDefaultDataProvider<T>(apiEndpoint: string): ListDataProvider<T> {
  return {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      return await apiCall(
        "GET",
        apiEndpoint,
        convertListStateToQueryParameters(state),
        {signal: options.signal}
      ) as ListDataResponse<T>;
    }
  };
}
