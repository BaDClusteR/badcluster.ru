import {createContext} from "react";

/**
 * Bumped by EntityForm when it rewrites blocks values itself (e.g. the typographer).
 * BlocksField re-renders the editor on every bump: a plain value change is ignored
 * right after typing, so without this the editor would keep showing the old text.
 */
export const BlocksRevisionContext = createContext(0);
