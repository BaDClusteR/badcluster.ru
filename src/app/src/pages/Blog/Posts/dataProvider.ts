import {PageRow} from "./types";
import {ListDataProvider, ListDataProviderRequestOptions, ListState} from "@/components/List/types.ts";
import apiCall from "@/utils/apiCall";
import convertListStateToQueryParameters from "@/components/List/utils/convertListStateToQueryParameters";

const dataProvider: ListDataProvider<PageRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
        const rows = await apiCall(
            'GET',
            'posts',
            convertListStateToQueryParameters(state),
            {signal: options.signal}
        ) as {posts: PageRow[]};

        return {
            rows: rows.posts,
            total: rows.posts.length
        }
    }
}

export default dataProvider;
