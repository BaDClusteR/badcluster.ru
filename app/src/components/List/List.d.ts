import { EntityRow, ListProps } from "@/components/List/types";
export declare function List<T extends EntityRow>({ name, permissions, defaults, dataProvider, columns, title, searchPlaceHolder, getEditLink, onAdd, onDelete, addButtonTitle, getDeleteConfirmationTitle, getDeleteConfirmationText }: ListProps<T>): import("react/jsx-runtime").JSX.Element;
