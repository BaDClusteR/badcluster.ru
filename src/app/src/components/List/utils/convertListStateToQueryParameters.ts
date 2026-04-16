import {ListRequestParameters} from "../types";
import {ListState} from "../types";

export default function convertListStateToQueryParameters(listState: ListState): ListRequestParameters {
    const result: ListRequestParameters = {
        perPage: listState.table.perPage,
        page: listState.table.page,
    };

    if (listState.filter) {
        result.filter = listState.filter;
    }

    if (listState.table.sortBy) {
        result.sortBy = listState.table.sortBy;

        if (listState.table.sortDir) {
            result.sortDir = listState.table.sortDir;
        }
    }

    return result;
}
