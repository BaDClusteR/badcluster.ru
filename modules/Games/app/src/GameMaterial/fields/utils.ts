import {File, Optional} from "@admin/types";
import {ReactNode} from "react";
import classes from "./FileBlock.module.css";

function getMonth(month: number): string {
  switch (month) {
    case 0:
      return "янв";
    case 1:
      return "фев";
    case 2:
      return "мар";
    case 3:
      return "апр";
    case 4:
      return "мая";
    case 5:
      return "июн";
    case 6:
      return "июл";
    case 7:
      return "авг";
    case 8:
      return "сен";
    case 9:
      return "окт";
    case 10:
      return "ноя";
    case 11:
      return "дек";
  }

  return "";
}

export default function formatDate(date: Optional<string>): string {
  if (!date) {
    return "";
  }

  const dt = new Date(date);
  return `${dt.getDate()} ${getMonth(dt.getMonth())} ${dt.getFullYear()}`;
}
