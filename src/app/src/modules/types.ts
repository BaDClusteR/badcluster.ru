import type { ComponentType } from 'react';

/** Descriptor returned by the backend API for each registered module */
export interface ModuleDescriptor {
  /** Unique module identifier, e.g. "analytics" */
  id: string;
  /** Display label for the sidebar menu */
  label: string;
  /** Route path relative to /admin/, e.g. "analytics" */
  path: string;
  /** Tabler icon name, e.g. "chart-bar" */
  icon?: string;
  /** Sort order in the sidebar */
  order?: number;
}

/** Resolved module ready for rendering */
export interface ResolvedModule extends ModuleDescriptor {
  component: ComponentType;
}