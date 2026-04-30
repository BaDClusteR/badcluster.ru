import type {ReactNode} from "react";
import {FormErrors, UseFormReturnType} from "@mantine/form";

export type FieldType =
    | "text"
    | "textarea"
    | "number"
    | "select"
    | "switch"
    | "blocks"
    | "heading"
    | "group";

export interface SelectOption {
    value: string;
    label: string;
}

export interface EntityFormRenderOptions {
    loading?: boolean
}

export interface BaseFieldDef<T> {
    /** Layout hint: `full` (default) or `half` width. */
    span?: "full" | "half",
    /** Group consecutive fields with the same fieldset value into a `<Fieldset>`. */
    fieldset?: string,
    /** Visual role: `primary` fields blend with background, `secondary` are shown in a card. */
    role?: "primary" | "secondary",
    required?: boolean,
    /** For `group` type. Second arg provides loading state when data is being fetched. */
    render?: (
        form: UseFormReturnType<T, T, (values: T) => FormErrors>,
        options?: EntityFormRenderOptions
    ) => ReactNode,
    hint?: ReactNode
}

export interface FieldDef<T> {
    /** Field key in form values. Optional for `heading` and `group` types. */
    name?: keyof T;
    /** Field label. Optional for `group` type. */
    label?: string;
    type: FieldType;
    placeholder?: string;
    /** For `select` type. */
    options?: SelectOption[];
}

export interface FieldGroupDef<T> extends BaseFieldDef<T> {
    type: "group"
}

/** Async data provider for EntityForm — fetches entity data via React Query. */
export interface EntityFormDataProvider<T> {
    queryKey: unknown[],
    getData: (signal?: AbortSignal) => Promise<T>
}

export interface EntityFormProps<T extends Record<string, unknown>> {
    fields: FieldDef<T>[],
    dataProvider: EntityFormDataProvider<T>,
    onSubmit: (values: T) => Promise<void> | void,
    submitLabel?: string,
    onCancel?: () => void,
    notFoundText?: string,
    notFoundBtnCaption?: string
}
