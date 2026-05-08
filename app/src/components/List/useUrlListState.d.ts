import type { ListStateManager, PartialListState } from "./types";
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
export declare function useUrlListState(options?: ListStateOptions): ListStateManager;
