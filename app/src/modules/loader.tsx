import { registerRemotes, loadRemote } from '@module-federation/runtime';
import type { ModuleDescriptor, ResolvedModule } from './types';
import type { ComponentType } from 'react';

/**
 * Dynamically registers a remote and loads its default export as a route component.
 *
 * Each remote module is expected to expose a `./routes` entry with:
 *   export default ComponentType  — a React component containing <Routes>
 */
export async function resolveModule(descriptor: ModuleDescriptor): Promise<ResolvedModule> {
  const remoteName = descriptor.id;

  try {
    registerRemotes([
      {
        name: remoteName,
        entry: descriptor.remoteEntry,
        type: 'module',
      },
    ]);

    const mod = await loadRemote<{ default: ComponentType }>(`${remoteName}/routes`);
    const component: ComponentType = mod?.default ?? (() => (
      <div style={{ padding: 24, color: '#e55' }}>
        Module <strong>{remoteName}</strong> has no default export.
      </div>
    ));

    return { ...descriptor, component };
  } catch (err) {
    console.error(`Failed to load module "${remoteName}":`, err);
    return {
      ...descriptor,
      component: () => (
        <div style={{ padding: 24, color: '#e55' }}>
          Failed to load module: <strong>{remoteName}</strong>
        </div>
      ),
    };
  }
}