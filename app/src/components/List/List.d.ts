import {EntityRow, ListProps} from "@admin/types";

export declare function List<T extends EntityRow>(
  {
    name,
    permissions,
    dataProvider,
    columns,
    onDelete,
    links,
    labels
  }: ListProps<T>
): import("react/jsx-runtime").JSX.Element;
