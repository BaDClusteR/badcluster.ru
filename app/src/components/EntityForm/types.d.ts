import type { ComponentType, ReactNode } from "react";
import { FormErrors, UseFormReturnType } from "@mantine/form";
export type CommonFieldType = "text" | "textarea" | "number" | "switch" | "blocks";
export interface SelectOption {
    value: string;
    label: string;
}
/** Components provided by EntityForm to group render functions. */
export interface EntityFormComponents {
    BlocksField: ComponentType<{
        label?: string;
        description?: ReactNode;
        placeholder?: string;
        value: unknown;
        onChange: (data: unknown) => void;
        className?: string;
    }>;
    FieldGroup: ComponentType<{
        children: ReactNode;
    }>;
}
export interface EntityFormRenderOptions<C = unknown> {
    loading?: boolean;
    context?: C;
    components: EntityFormComponents;
}
export interface FieldDefBase {
    span?: "full" | "half";
    fieldset?: string;
    role?: "primary" | "secondary";
    required?: boolean;
    hint?: ReactNode;
}
export interface FieldDefCommon<T> extends FieldDefBase, FieldDefNamed<T> {
    type: CommonFieldType;
    placeholder?: string;
}
export interface FieldDefNamed<T> {
    name: keyof T;
    label: string;
}
export type FieldDef<T, C = unknown> = FieldDefCommon<T> | FieldDefGroup<T, C> | FieldDefSelect<T> | FieldDefHeading | FieldDefDateTime<T> | FieldDefSlug<T>;
export interface FieldDefGroup<T, C = unknown> extends FieldDefBase {
    type: "group";
    render?: (form: UseFormReturnType<T, T, (values: T) => FormErrors>, options?: EntityFormRenderOptions<C>) => ReactNode;
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
