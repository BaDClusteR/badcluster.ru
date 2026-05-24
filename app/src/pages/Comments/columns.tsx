import {BadgeRed, BadgeYellow, BadgeGray, BadgeGreen} from "@/components/primitives/Badge";
import {ColumnDef} from "@admin/types";
import {CommentRow} from "./types.ts";
import classes from "./Comments.module.css";
import {Link} from "react-router";

const columns: ColumnDef<CommentRow>[] = [
  {
    key: "date",
    header: "Дата",
    sortable: true,
    link: true,
    nowrap: true
  },
  {
    key: "name",
    header: "Никнейм",
    sortable: true
  },
  {
    key: "comment",
    header: "Комментарий",
    render: row => <span className={classes.comment} dangerouslySetInnerHTML={{__html: row.comment}}/>
  },
  {
    key: "page",
    header: "Страница",
    render: row => row.pageLink
      ? <Link to={row.pageLink}>{row.page}</Link>
      : row.page
  },
  {
    key: "status",
    header: "Статус",
    render: row => {
      if (row.status === "A") {
        return <BadgeGreen className={classes.approvedBadge}>Подтвержден</BadgeGreen>;
      }

      if (row.status === "M") {
        return <BadgeGray>На модерации</BadgeGray>;
      }

      if (row.status === "D") {
        return <BadgeRed>Отклонен</BadgeRed>;
      }

      return <BadgeYellow>Неизвестный статус: {row.status}</BadgeYellow>;
    }
  }
];

export default columns;
