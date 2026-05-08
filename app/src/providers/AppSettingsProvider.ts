import type { NavItemDescriptor, ModuleDescriptor } from '@/modules/types';

let cached: Record<string, unknown> | null = null;

function parse(): Record<string, unknown> {
  if (cached) return cached;

  const el = document.getElementById('app-settings');
  if (!el?.textContent) {
    cached = {};
    return cached;
  }

  try {
    cached = JSON.parse(el.textContent.trim());
  } catch {
    console.error('Failed to parse app-settings JSON');
    cached = {};
  }

  return cached!;
}

export function get<T = unknown>(key: string): T | undefined {
  return parse()[key] as T | undefined;
}

export function getNavigation(): NavItemDescriptor[] {
  return get<NavItemDescriptor[]>('nav') ?? [];
}

export function getModules(): ModuleDescriptor[] {
  return get<ModuleDescriptor[]>('modules') ?? [];
}