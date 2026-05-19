import type { ComponentType } from 'react';

/** A single navigation item returned by the backend */
export interface NavItemDescriptor {
  label: string;
  path?: string;
  icon?: string;
  position?: number;
  children?: NavItemDescriptor[];
}

/** Module descriptor returned by the backend */
export interface ModuleDescriptor {
  /** Unique module identifier, e.g. "blog" */
  id: string;
  /** Route path relative to /admin/, e.g. "blog" */
  path: string;
  /** URL to the remote entry file */
  remoteEntry: string;
}

/** Resolved module ready for rendering */
export interface ResolvedModule extends ModuleDescriptor {
  component: ComponentType;
}

/** Full response from GET /api/admin/modules */
export interface ModulesApiResponse {
  nav: NavItemDescriptor[];
  modules: ModuleDescriptor[];
}
