import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import type {ListState, ListStateManager, PartialListState} from "./types";
import deepMerge from "@/utils/deepMerge";
import {SortDir, TableState} from "@/components/DataTable";
import {Optional} from "@/types";

const BASE_DEFAULTS: ListState = {
    table: {
        page: 1,
        perPage: 25,
        sortBy: null,
        sortDir: 'asc'
    },
    filter: ''
};

export interface ListStateOptions {
    /** Override initial defaults (e.g. { perPage: 50, sortBy: 'createdAt' }). */
    defaults?: PartialListState;
}

/**
 * Backs table state with URL search params via react-router.
 *
 * - Only non-default values are written to the URL (clean URLs).
 * - Changing filter/sort resets page to 1 automatically.
 * - Navigation uses { replace: true } so the back button isn't spammed.
 */
export function useUrlListState(options: ListStateOptions = {}): ListStateManager {
    const { defaults: userDefaults } = options;
    const [searchParams, setSearchParams] = useSearchParams();

    const defaults = useMemo<ListState>(
        () => deepMerge(BASE_DEFAULTS, (userDefaults || {})) as ListState,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const state: ListState = useMemo(() => {
        const pageRaw = Number(searchParams.get('page'));
        const perPageRaw = Number(searchParams.get('perPage'));

        return {
            table: {
                page: Number.isFinite(pageRaw) && pageRaw > 0 ? pageRaw : defaults.table.page,
                perPage: Number.isFinite(perPageRaw) && perPageRaw > 0 ? perPageRaw : defaults.table.perPage,
                sortBy: searchParams.get('sortBy') ?? defaults.table.sortBy,
                sortDir: (searchParams.get('sortDir') as SortDir | null) ?? defaults.table.sortDir
            },
            filter: searchParams.get('filter') ?? defaults.filter
        };
    }, [searchParams, defaults]);

    const setState = useCallback(
        (patch: PartialListState) => {
            const merged = deepMerge(state, patch) as ListState;

            // Reset page whenever filter/sort changes (unless page is in the same patch)
            const resetPage =
                !('page' in patch) &&
                ('filter' in patch || 'sortBy' in patch || 'sortDir' in patch);

            if (resetPage) {
                merged.table.page = 1;
            }

            setSearchParams(
                (prev) => {
                    const next = new URLSearchParams(prev);

                    const write = (
                        name: (keyof TableState) | 'filter',
                        value: Optional<string | number>
                    ) => {
                        const str = value == null ? '' : String(value);
                        let defStr;
                        if (name === 'filter') {
                            defStr = defaults.filter ?? '';
                        } else {
                            defStr = defaults.table[name]
                                ? String(defaults.table[name])
                                : '';
                        }

                        if (str === '' || str === defStr) {
                            next.delete(name);
                        } else {
                            next.set(name, str);
                        }
                    };

                    write('page', merged.table.page);
                    write('perPage', merged.table.perPage);
                    write('sortBy', merged.table.sortBy);
                    write('sortDir', merged.table.sortDir);
                    write('filter', merged.filter);

                    return next;
                },
                { replace: false },
            );
        },
        [state, setSearchParams, defaults],
    );

    return { state, setState };
}
