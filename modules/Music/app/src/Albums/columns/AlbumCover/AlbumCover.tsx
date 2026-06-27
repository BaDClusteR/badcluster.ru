import {Media, Optional} from "@admin/types";
import React, {ReactNode} from "react";
import classes from "./AlbumCover.module.css";
import {useAdminCore} from "../../../admin/useAdminCore";

export default function AlbumCover({media}: { media: Optional<Media> }): ReactNode {
  const {Picture} = useAdminCore();

  return <Picture media={media} width={130} fallback="—" className={classes.cover}/>;
}
