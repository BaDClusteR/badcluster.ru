import {FormErrors, UseFormReturnType} from "@mantine/form";
import {Comment, CommentContext} from "@/pages/Comment/types.ts";
import type {EntityFormRenderOptions} from "@admin/types";
import classes from "../Comment.module.css";
import Ip from "@/components/EntityForm/Ip/Ip.tsx";
import {Link} from "react-router";
import {ReactNode} from "react";

const Row = ({header, children}: { header: ReactNode, children?: ReactNode }): ReactNode => (
  <div className={classes.tr} role="row">
    <div className={classes.th} role="cell">{header}</div>
    <div className={classes.td} role="cell">{children}</div>
  </div>
);

export default function renderCommentInfo(
  _form: UseFormReturnType<Comment, Comment, (values: Comment) => FormErrors>,
  options: EntityFormRenderOptions<CommentContext>
): ReactNode {
  return options.loading
    ? null
    : <div className={classes.table} role="table">
      <Row header="IP адрес: ">
        <Ip info={options.context?.geoIp}/>
      </Row>
      <Row header="Страница: ">
        <Link to={options.context?.pageLink ?? ""}>{options.context?.page}</Link>
      </Row>
      {
        options.context?.email &&
        <Row header="Email: ">
          <a href={`mailto:${options.context.email}`}>{options.context.email}</a>
        </Row>
      }
      {
        options.context?.parent &&
        <Row header="Родительский коммент: ">
          <div className={classes.parentComment}>
            <Link to={options.context.parent.link}>{options.context.parent.title}</Link>
            <p className={classes.parentCommentText} dangerouslySetInnerHTML={{__html: options.context?.parent.text}}/>
          </div>
        </Row>
      }
    </div>;
}
