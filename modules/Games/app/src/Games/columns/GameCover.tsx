import {Media, MediaThumbnail, Optional} from "@admin/types";
import React, {ReactNode} from "react";
import classes from "./GameCover.module.css";

function renderPictureSource(thumb: Optional<MediaThumbnail>) {
  if (!thumb) {
    return null;
  }

  return <source srcSet={thumb.url} width={thumb.width} height={thumb.height} type={thumb.mime}/>;
}

export default function GameCover({media}: { media: Optional<Media> }): ReactNode {
  if (media) {
    let sources: { [key: string]: MediaThumbnail } = {};

    media.thumbs?.forEach((thumb) => {
      if (
        thumb.width >= 200
        && (
          !sources[thumb.mime]
          || sources[thumb.mime].width > thumb.width
        )
      ) {
        sources[thumb.mime] = thumb;
      }
    });

    return <a href={media.url} target="_blank" className={classes.link}>
      <picture className={classes.cover}>
        {renderPictureSource(sources["image/avif"])}
        {renderPictureSource(sources["image/webp"])}
        <img src={media.url} width={media.width} height={media.height} alt={media.alt}/>
      </picture>
    </a>;
  }

  return "—";
}
