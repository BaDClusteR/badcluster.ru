import { lazy, type ComponentType } from 'react';
import type { ModuleDescriptor } from './types';

/**
 * Resolves a module descriptor to a React component.
 *
 * Convention: each module places its entry component at
 *   /modules/{id}/app/index.tsx
 *
 * The component is loaded lazily so the main bundle stays small.
 * If the module cannot be loaded, a fallback error component is shown.
 */
export async function resolveModuleComponent(
  descriptor: ModuleDescriptor,
): Promise<ComponentType> {
  return lazy(
    () =>
      import(
        /* @vite-ignore */
        `/modules/${descriptor.id}/app/index.tsx`
      ).catch(() => ({
        default: () => (
          <div style={{ padding: 24, color: '#e55' }}>
            Failed to load module: <strong>{descriptor.id}</strong>
          </div>
        ),
      })),
  );
}