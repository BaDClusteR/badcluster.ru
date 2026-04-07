import type { ReactNode } from 'react';

export type FieldType =
  | 'text'
  | 'textarea'
  | 'number'
  | 'select'
  | 'switch'
  | 'blocks';

export interface SelectOption {
  value: string;
  label: string;
}

export interface FieldDef {
  name: string;
  label: string;
  type: FieldType;
  description?: ReactNode;
  placeholder?: string;
  required?: boolean;
  /** For `select` type. */
  options?: SelectOption[];
  /** Layout hint: `full` (default) or `half` width. */
  span?: 'full' | 'half';
}

export interface EntityFormProps<T extends Record<string, unknown>> {
  fields: FieldDef[];
  initialValues: T;
  onSubmit: (values: T) => Promise<void> | void;
  submitLabel?: string;
  onCancel?: () => void;
}
