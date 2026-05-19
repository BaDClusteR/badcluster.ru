import { useContext } from 'react';
import type { AdminCore } from '@admin/types';

export type { AdminCore };

/**
 * Access host's core components and utilities.
 * The host stores the context on a global so all remotes share the same reference.
 */
export function useAdminCore(): AdminCore {
  const ctx = (globalThis as any).__adminCoreContext;
  if (!ctx) {
    throw new Error('AdminCoreContext not found. Is the host running?');
  }

  const value: AdminCore = useContext(ctx);
  if (!value) {
    throw new Error('useAdminCore must be used within a route rendered by the host.');
  }

  return value;
}