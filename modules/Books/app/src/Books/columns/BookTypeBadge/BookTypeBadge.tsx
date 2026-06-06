import React from "react";
import {useAdminCore} from "../../../admin/useAdminCore";

export default function BookTypeBadge(props: { type: "A" | "T" }): React.JSX.Element {
  const {BadgeGreen, BadgeGray} = useAdminCore();

  return (props.type === "A")
    ? <BadgeGreen>Авторское</BadgeGreen>
    : <BadgeGray>Перевод</BadgeGray>;
}
