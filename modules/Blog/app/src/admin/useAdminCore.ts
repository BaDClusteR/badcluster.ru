import { useContext } from 'react';

/** All core components/utilities provided by the host. */
export interface AdminCore {
  EntityForm: any;
  List: any;
  convertListStateToQueryParameters: (state: any) => any;
  BadgeGray: any;
  BadgeGreen: any;
  apiCall: (...args: any[]) => Promise<any>;
  notify: {
    success: (...args: any[]) => void;
    error: (...args: any[]) => void;
    info: (...args: any[]) => void;
    warning: (...args: any[]) => void;
  };
}

/**
 * Access host's core components and utilities.
 * The host stores the context on a global so all remotes share the same reference.
 */
export function useAdminCore(): AdminCore {
  const ctx = (globalThis as any).__adminCoreContext;
  if (!ctx) {
      throw new Error('AdminCoreContext not found. Is the host running?')
  }

  const value: AdminCore = useContext(ctx);
  if (!value) {
      throw new Error('useAdminCore must be used within a route rendered by the host.');
  }

  return value;
}
