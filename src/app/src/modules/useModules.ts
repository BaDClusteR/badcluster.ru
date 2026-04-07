import { useState, useEffect } from 'react';
import type { ResolvedModule, ModuleDescriptor } from './types';
import { resolveModuleComponent } from './loader.tsx';

/**
 * Fetches the list of registered modules from the backend
 * and resolves their components via dynamic import.
 *
 * Backend endpoint: GET /api/admin/modules
 * Returns: { modules: ModuleDescriptor[] }
 */
export function useModules() {
  const [modules, setModules] = useState<ResolvedModule[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const res = await fetch('/api/admin/modules');
        if (!res.ok) {
          setLoading(false);
          return;
        }
        const data: { modules: ModuleDescriptor[] } = await res.json();

        const resolved = await Promise.all(
          data.modules.map(async (desc) => {
            const component = await resolveModuleComponent(desc);
            return { ...desc, component } as ResolvedModule;
          }),
        );

        if (!cancelled) {
          resolved.sort((a, b) => (a.order ?? 100) - (b.order ?? 100));
          setModules(resolved);
        }
      } catch {
        // API not available yet — that's fine, no modules
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    load();
    return () => { cancelled = true; };
  }, []);

  return { modules, loading };
}