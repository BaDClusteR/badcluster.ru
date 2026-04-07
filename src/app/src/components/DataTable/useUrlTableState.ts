import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import type { SortDir, TableState, TableStateManager } from './types';

const BASE_DEFAULTS: TableState = {
  page: 1,
  perPage: 25,
  sortBy: null,
  sortDir: 'asc',
  filter: '',
};

export interface UrlTableStateOptions {
  /** Prefix for query param names — use it to host multiple tables on one page. */
  prefix?: string;
  /** Override initial defaults (e.g. { perPage: 50, sortBy: 'createdAt' }). */
  defaults?: Partial<TableState>;
}

/**
 * Backs table state with URL search params via react-router.
 *
 * - Only non-default values are written to the URL (clean URLs).
 * - Changing filter/sort resets page to 1 automatically.
 * - Navigation uses { replace: true } so the back button isn't spammed.
 */
export function useUrlTableState(options: UrlTableStateOptions = {}): TableStateManager {
  const { prefix = '', defaults: userDefaults } = options;
  const [searchParams, setSearchParams] = useSearchParams();

  const defaults = useMemo<TableState>(
    () => ({ ...BASE_DEFAULTS, ...userDefaults }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [],
  );

  const k = useCallback(
    (name: string) => (prefix ? `${prefix}_${name}` : name),
    [prefix],
  );

  const state: TableState = useMemo(() => {
    const pageRaw = Number(searchParams.get(k('page')));
    const perPageRaw = Number(searchParams.get(k('perPage')));
    return {
      page: Number.isFinite(pageRaw) && pageRaw > 0 ? pageRaw : defaults.page,
      perPage: Number.isFinite(perPageRaw) && perPageRaw > 0 ? perPageRaw : defaults.perPage,
      sortBy: searchParams.get(k('sortBy')) ?? defaults.sortBy,
      sortDir: (searchParams.get(k('sortDir')) as SortDir | null) ?? defaults.sortDir,
      filter: searchParams.get(k('filter')) ?? defaults.filter,
    };
  }, [searchParams, k, defaults]);

  const setState = useCallback(
    (patch: Partial<TableState>) => {
      const merged: TableState = { ...state, ...patch };

      // Reset page whenever filter/sort changes (unless page is in the same patch)
      const resetPage =
        !('page' in patch) &&
        ('filter' in patch || 'sortBy' in patch || 'sortDir' in patch);
      if (resetPage) merged.page = 1;

      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev);

          const write = (name: keyof TableState, value: string | number | null) => {
            const str = value == null ? '' : String(value);
            const defStr =
              defaults[name] == null ? '' : String(defaults[name]);
            if (str === '' || str === defStr) {
              next.delete(k(name));
            } else {
              next.set(k(name), str);
            }
          };

          write('page', merged.page);
          write('perPage', merged.perPage);
          write('sortBy', merged.sortBy);
          write('sortDir', merged.sortDir);
          write('filter', merged.filter);

          return next;
        },
        { replace: true },
      );
    },
    [state, setSearchParams, k, defaults],
  );

  return { state, setState };
}