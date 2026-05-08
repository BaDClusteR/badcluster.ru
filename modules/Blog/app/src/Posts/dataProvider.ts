import { PageRow } from "./types";
import type { ListDataProvider, ListDataProviderRequestOptions, ListState } from "../admin/List";
import apiCall from "../admin/apiCall";
import { getConvertListStateToQueryParameters } from "../admin/List";

const dataProvider: ListDataProvider<PageRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
        const convertListStateToQueryParameters = await getConvertListStateToQueryParameters();
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