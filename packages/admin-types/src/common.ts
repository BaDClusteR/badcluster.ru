export type StringKeyObject = {
  [key: string]: any
}

export type Nullable<T> = T | null;

export type Optional<T> = T | null | undefined;

export interface SelectOption {
  value: string,
  label: string
}

export type SelectOptions = SelectOption[];
