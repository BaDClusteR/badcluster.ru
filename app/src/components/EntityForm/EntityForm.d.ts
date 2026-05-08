import "@mantine/dates/styles.layer.css";
import type { EntityFormProps } from "./types";
export declare function EntityForm<T extends Record<string, unknown>, C = unknown>({ fields, dataProvider, context, onSubmit, submitLabel, onCancel, notFoundText, notFoundBtnCaption }: EntityFormProps<T, C>): import("react/jsx-runtime").JSX.Element;
