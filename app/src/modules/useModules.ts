import { useState, useEffect } from 'react';
import type { ResolvedModule, NavItemDescriptor } from './types';
import { resolveModule } from './loader';
import { getNavigation, getModules } from '@/providers/AppSettingsProvider';

/**
 * Reads navigation and module list from the inline app-settings JSON,
 * then dynamically loads each remote module.
 */
export function useModules() {
  const [nav] = useState<NavItemDescriptor[]>(() => getNavigation());
  const [modules, setModules] = useState<ResolvedModule[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const descriptors = getModules();

        const resolved = await Promise.all(
          descriptors.map((desc) => resolveModule(desc)),
        );

        if (!cancelled) {
          setModules(resolved);
        }
      } catch {
        // Module loading failed — continue without modules
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    load();
    return () => { cancelled = true; };
  }, []);

  return { nav, modules, loading };
}