import type {ComponentType, ReactNode} from "react";
// noinspection TypeScriptCheckImport
// @ts-ignore
import type {FormErrors, UseFormReturnType} from "@mantine/form";
import {Optional} from "./common";

export type CommonFieldType =
  | "number"
  | "switch"
  | "blocks";

export type FieldType =
  | CommonFieldType
  | "text"
  | "textarea"
  | "select"
  | "heading"
  | "datetime"
  | "group"
  | "slug"
  | "image";

export interface SelectOption {
  value: string;
  label: string;
}

export interface EntityFormComponents {
  BlocksField: ComponentType<{
    label?: string;
    description?: ReactNode;
    placeholder?: string;
    value: unknown;
    onChange: (data: unknown) => void;
    className?: string;
  }>;
  FieldGroup: ComponentType<{ children: ReactNode; isSubmitting?: boolean }>;
}

export interface EntityFormRenderOptions<C = unknown> {
  loading?: boolean;
  submitting?: boolean;
  context?: C;
  components: EntityFormComponents;
}

export interface FieldDefBase {
  span?: "full" | "half";
  fieldset?: string;
  role?: "primary" | "secondary";
  required?: boolean;
  hint?: ReactNode;
  validate?: (value: unknown) => string | null;
}

export interface FieldDefNamed<T> {
  name: keyof T;
  label: string;
}

export interface FieldDefText<T> extends FieldDefBase, FieldDefNamed<T> {
  type: "text" | "textarea";
  placeholder?: string;
  softMaxLength?: number;
}

export interface FieldDefCommon<T> extends FieldDefBase, FieldDefNamed<T> {
  type: CommonFieldType;
  placeholder?: string;
}

export interface FieldDefGroup<T, C = unknown> extends FieldDefBase {
  type: "group";
  render?: (
    form: UseFormReturnType<T, T, (values: T) => FormErrors>,
    options: EntityFormRenderOptions<C>,
    values: Optional<T>
  ) => ReactNode;
}

export interface FieldDefSelect<T> extends FieldDefBase, FieldDefNamed<T> {
  type: "select";
  placeholder?: string;
  options?: SelectOption[];
}

export interface FieldDefHeading extends FieldDefBase {
  type: "heading";
  label: string;
}

export interface FieldDefDateTime<T> extends FieldDefBase, FieldDefNamed<T> {
  type: "datetime";
  valueFormat?: string;
  clearable?: boolean;
}

export interface FieldDefSlug<T> extends FieldDefBase, FieldDefNamed<T> {
  type: "slug";
  placeholder?: string;
  url: (slug: string) => string;
}

export interface FieldDefImage<T> extends FieldDefBase, FieldDefNamed<T> {
  type: "image";
  previewWidth?: number | string;
  thumbnailWidth?: number;
  thumbnailHeight?: number;
  uploadPurpose?: string;
  showAlt?: boolean;
}

export type FieldDef<T, C = unknown> =
  | FieldDefCommon<T>
  | FieldDefGroup<T, C>
  | FieldDefSelect<T>
  | FieldDefHeading
  | FieldDefDateTime<T>
  | FieldDefSlug<T>
  | FieldDefImage<T>
  | FieldDefText<T>;

export interface EntityFormDataProvider<T> {
  queryKey: unknown[];
  entityId: number;
  getData: (signal?: AbortSignal) => Promise<T>;
}

export interface EntityCreatedResponse {
  id: number,

  [key: string]: any
}

export interface EntityFormProps<T, C = unknown> {
  fields: FieldDef<T, C>[],
  dataProvider?: EntityFormDataProvider<T>,
  initialValues?: Partial<T>,
  context?: C,
  title?: string | ((data: Optional<T>, context: Optional<C>) => ReactNode),
  webPath: string,
  apiEndpoint: string,
  labels: {
    notFound: {
      text: string,
      btnCaption: string,
    },
    submit: {
      create?: string,
      update: string,
    },
    messages: {
      onCreate?: string,
      onUpdate: string
    }
  }
}

export interface GeoIp {
  ip: string,
  country: string,
  countryCode: string,
  city: string,
  rangeStart: string,
  rangeEnd: string
}

export interface Media {
  id: number,
  url: string,
  width: number,
  height: number,
  mime: string,
  alt: string,
  thumbs?: MediaThumbnail[]
}

export interface MediaThumbnail {
  width: number,
  height: number,
  url: string,
  mime: string
}
