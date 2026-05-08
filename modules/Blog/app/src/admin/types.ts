import type { ComponentType, ReactNode } from 'react';
import type { FormErrors, UseFormReturnType } from '@mantine/form';

// --- EntityForm types ---

export interface EntityFormComponents {
  BlocksField: ComponentType<{
    label?: string;
    description?: ReactNode;
    placeholder?: string;
    value: unknown;
    onChange: (data: unknown) => void;
    className?: string;
  }>;
  FieldGroup: ComponentType<{ children: ReactNode }>;
}

export interface EntityFormRenderOptions<C = unknown> {
  loading?: boolean;
  context?: C;
  components: EntityFormComponents;
}

export interface EntityFormDataProvider<T> {
  queryKey: unknown[];
  getData: (signal?: AbortSignal) => Promise<T>;
}

export interface FieldDef<T, C = unknown> {
  name?: keyof T;
  label?: string;
  type: string;
  hint?: ReactNode;
  placeholder?: string;
  required?: boolean;
  options?: { value: string; label: string }[];
  span?: 'full' | 'half';
  fieldset?: string;
  role?: 'primary' | 'secondary';
  render?: (
    form: UseFormReturnType<T, T, (values: T) => FormErrors>,
    options?: EntityFormRenderOptions<C>,
  ) => ReactNode;
  url?: (slug: string) => string;
  clearable?: boolean;
  valueFormat?: string;
}

// --- List types ---

export interface EntityRow {
  id: number;
}

export interface ListState {
  page: number;
  perPage: number;
  sort?: string;
  sortDir?: string;
  search?: string;
}

export interface ListDataProviderRequestOptions {
  signal?: AbortSignal;
}

export interface ListDataProvider<T extends EntityRow> {
  getData: (
    state: ListState,
    options: ListDataProviderRequestOptions,
  ) => Promise<{ rows: T[]; total: number }>;
}

// --- DataTable types ---

export interface ColumnDef<T> {
  key: string;
  subKey?: string;
  header: string;
  sortable?: boolean;
  width?: number;
  render?: (row: T) => ReactNode;
  link?: (row: T) => string;
}