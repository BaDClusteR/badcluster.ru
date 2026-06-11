import type {ComponentType, ReactNode} from "react";
// noinspection TypeScriptCheckImport
// @ts-ignore
import type {FormErrors, UseFormReturnType} from "@mantine/form";
import {Optional, StringKeyObject} from "./common";

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
    showSettings?: boolean;
    uploadPurpose?: string;
  }>;
  FieldGroup: ComponentType<{
    children: ReactNode,
    isSubmitting?: boolean,
    focusMode?: boolean,
    className?: string
  }>;
}

export interface EntityFormRenderOptions<C = unknown> {
  loading?: boolean;
  submitting?: boolean;
  context?: C;
  components: EntityFormComponents;
}

export interface FieldDefBase<T> {
  span?: "full" | "half";
  fieldset?: string;
  role?: "primary" | "secondary";
  required?: boolean;
  hint?: ReactNode;
  validate?: (value: unknown) => string | null;
  /** Conditionally show/hide this field based on current form values. */
  visible?: (values: T) => boolean;
}

export interface FieldDefNamed<T> {
  name: keyof T;
  label: string;
}

export interface FieldDefText<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "text" | "textarea";
  placeholder?: string;
  softMaxLength?: number;
}

export interface FieldDefCommon<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: CommonFieldType;
  placeholder?: string;
}

export interface FieldDefGroup<T, C = unknown> extends FieldDefBase<T> {
  type: "group";
  render?: (
    form: UseFormReturnType<T, T, (values: T) => FormErrors>,
    options: EntityFormRenderOptions<C>,
    values: Optional<T>
  ) => ReactNode;
}

export interface FieldDefSelect<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "select";
  placeholder?: string;
  options?: SelectOption[];
}

export interface FieldDefHeading<T> extends FieldDefBase<T> {
  type: "heading";
  label: string;
}

export interface FieldDefDateTime<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "datetime";
  valueFormat?: string;
  clearable?: boolean;
}

export interface FieldDefSlug<T, C = unknown> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "slug";
  placeholder?: string;
  url: (slug: string, values: T, context?: C) => string;
}

export interface FieldDefImage<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "image";
  previewWidth?: number | string;
  thumbnailWidth?: number;
  thumbnailHeight?: number;
  uploadPurpose?: string;
  showAlt?: boolean;
}

export interface FieldDefFile<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "file";
  /** Upload endpoint. Defaults to /admin/api/upload. */
  uploadEndpoint?: string;
  /** Extra form fields to send with the upload. */
  uploadFields?: Record<string, string>;
  /** Accepted file types (e.g. ".zip,.pdf"). */
  accept?: string;
}

export interface FieldDefJson<T> extends FieldDefBase<T>, FieldDefNamed<T> {
  type: "json";
  /** Editor height in px. Defaults to 300. */
  height?: number;
}

export interface FieldDefSpacer<T> extends FieldDefBase<T> {
  type: "spacer";
}

export type FieldDef<T, C = unknown> =
  | FieldDefCommon<T>
  | FieldDefGroup<T, C>
  | FieldDefSelect<T>
  | FieldDefHeading<T>
  | FieldDefDateTime<T>
  | FieldDefSlug<T, C>
  | FieldDefImage<T>
  | FieldDefFile<T>
  | FieldDefJson<T>
  | FieldDefSpacer<T>
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
  preprocessValues?: (values: T, context: Optional<C>, isCreateMode: boolean) => StringKeyObject,
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

export interface File {
  id: number,
  filename: string,
  size: number,
  sizeHumanReadable: string,
  mime: string,
  url: string
}
