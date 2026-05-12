import type { ComponentType } from 'react';

export interface NavItemDescriptor {
  label: string;
  path?: string;
  icon?: string;
  position?: number;
  children?: NavItemDescriptor[];
}

export interface ModuleDescriptor {
  id: string;
  path: string;
  remoteEntry: string;
}

export interface ResolvedModule extends ModuleDescriptor {
  component: ComponentType;
}

export interface ModulesApiResponse {
  nav: NavItemDescriptor[];
  modules: ModuleDescriptor[];
}