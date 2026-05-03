import type {ReactNode} from "react";
import {FormErrors, UseFormReturnType} from "@mantine/form";

export type CommonFieldType =
    | "text"
    | "textarea"
    | "number"
    | "switch"
    | "blocks";

export type FieldType =
    | CommonFieldType
    | "select"
    | "heading"
    | "datetime"
    | "group"
    | "slug";

export interface SelectOption {
    value: string;
    label: string;
}

export interface EntityFormRenderOptions<C = unknown> {
    loading?: boolean,
    context?: C
}

export interface FieldDefBase {
    span?: "full" | "half",
    fieldset?: string,
    role?: "primary" | "secondary",
    required?: boolean,
    hint?: ReactNode
}

export interface FieldDefCommon<T> extends FieldDefBase, FieldDefNamed<T> {
    type: CommonFieldType;
    placeholder?: string;
}

export interface FieldDefNamed<T> {
    name: keyof T,
    label: string
}

export type FieldDef<T, C = unknown> = FieldDefCommon<T>
    | FieldDefGroup<T, C>
    | FieldDefSelect<T>
    | FieldDefHeading
    | FieldDefDateTime<T>
    | FieldDefSlug<T>;

export interface FieldDefGroup<T, C = unknown> extends FieldDefBase {
    type: "group",
    render?: (
        form: UseFormReturnType<T, T, (values: T) => FormErrors>,
        options?: EntityFormRenderOptions<C>
    ) => ReactNode
}

export interface FieldDefSelect<T> extends FieldDefBase, FieldDefNamed<T> {
    type: "select",
    placeholder?: string,
    options?: SelectOption[]
}

export interface FieldDefHeading extends FieldDefBase {
    type: "heading",
    label: string
}

export interface FieldDefDateTime<T> extends FieldDefBase, FieldDefNamed<T> {
    type: "datetime",
    valueFormat?: string,
    clearable?: boolean
}

export interface FieldDefSlug<T> extends FieldDefBase, FieldDefNamed<T> {
    type: "slug",
    placeholder?: string,
    url: (slug: string) => string
}

/** Async data provider for EntityForm — fetches entity data via React Query. */
export interface EntityFormDataProvider<T> {
    queryKey: unknown[],
    getData: (signal?: AbortSignal) => Promise<T>
}

export interface EntityFormProps<T, C = unknown> {
    fields: FieldDef<T, C>[],
    dataProvider: EntityFormDataProvider<T>,
    context?: C,
    onSubmit: (values: T) => Promise<void> | void,
    submitLabel?: string,
    onCancel?: () => void,
    notFoundText?: string,
    notFoundBtnCaption?: string
}
